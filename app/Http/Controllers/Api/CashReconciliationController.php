<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashReconciliation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $query = CashReconciliation::with(['user:id,name', 'branch:id,name'])->orderBy('date', 'desc');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->query('branch_id'));
        }
        
        $user = $request->user();
        if ($user && $user->active_role_id) {
            $role = \Spatie\Permission\Models\Role::find($user->active_role_id);
            if (!$role || !$role->hasPermissionTo('Cabang Read')) {
                $assignment = DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->where('model_type', get_class($user))
                    ->where('role_id', $user->active_role_id)
                    ->first();
                if ($assignment && $assignment->branch_id) {
                    $query->where('branch_id', $assignment->branch_id);
                }
            }
        }

        return response()->json($query->paginate(10));
    }

    public function monitoring(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        
        $branches = \App\Models\Branch::where('status', 'Aktif')->get();
        
        $closings = CashReconciliation::with('user:id,name')
            ->whereDate('date', $date)
            ->get()
            ->keyBy('branch_id');
            
        $result = $branches->map(function ($branch) use ($closings) {
            $closing = $closings->get($branch->id);
            return [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'is_closed' => $closing ? true : false,
                'closed_at' => $closing ? $closing->created_at : null,
                'closed_by' => $closing ? $closing->user->name : null,
                'variance' => $closing ? $closing->variance : null,
                'expected_cash' => $closing ? $closing->expected_cash : null,
                'actual_cash' => $closing ? $closing->actual_cash : null,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $result,
            'date' => $date
        ]);
    }

    public function getRequiredDate(Request $request)
    {
        $branchId = $request->query('branch_id');
        if (!$branchId) {
            return response()->json(['error' => 'Branch ID required'], 400);
        }

        $lastClosing = CashReconciliation::where('branch_id', $branchId)
            ->orderBy('date', 'desc')
            ->first();
            
        $today = Carbon::today()->toDateString();
        
        if ($lastClosing) {
            $nextRequired = Carbon::parse($lastClosing->date)->addDay()->toDateString();
            if ($nextRequired > $today) {
                $nextRequired = $today;
            }
            return response()->json(['date' => $nextRequired, 'last_closed' => $lastClosing->date]);
        }
        
        return response()->json(['date' => $today, 'last_closed' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'actual_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'date' => 'required|date',
            'manager_pin' => 'nullable|string',
        ]);

        $date = $validated['date'];
        $today = Carbon::today()->toDateString();
        $user = $request->user();

        // Enforce sequential date
        $lastClosing = CashReconciliation::where('branch_id', $validated['branch_id'])
            ->orderBy('date', 'desc')
            ->first();
            
        $expectedDate = $lastClosing ? Carbon::parse($lastClosing->date)->addDay()->toDateString() : $today;
        if ($expectedDate > $today) $expectedDate = $today;
        
        if ($date !== $expectedDate) {
            return response()->json(['message' => 'Urutan salah! Anda harus melakukan closing untuk tanggal ' . date('d M Y', strtotime($expectedDate)) . ' terlebih dahulu.'], 400);
        }

        // Require PIN if closing past date
        if ($date < $today) {
            if (empty($validated['manager_pin'])) {
                return response()->json(['message' => 'PIN Kepala Cabang diperlukan karena Anda melakukan closing untuk hari sebelumnya (Keterlambatan melewati batas jam 12 malam).'], 403);
            }
            
            // Verifikasi PIN
            $manager = \App\Models\User::whereHas('roles', function($q) {
                $q->whereIn('name', ['Admin Cabang', 'Super Admin', 'Kepala Cabang']);
            })->where('pos_pin', $validated['manager_pin'])->first();
            
            if (!$manager) {
                return response()->json(['message' => 'PIN Kepala Cabang tidak valid!'], 403);
            }
        }
        $user = $request->user();

        // Check if already reconciled today for this user/branch
        $existing = CashReconciliation::where('branch_id', $validated['branch_id'])
            ->where('user_id', $user->id)
            ->where('date', $date)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Cash reconciliation already submitted for today.'], 400);
        }

        // Calculate expected cash
        // This is a simplified calculation: just sum of sales for today by this user in this branch.
        $expectedCash = DB::table('sales')
            ->where('branch_id', $validated['branch_id'])
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->sum('total_amount');

        $variance = $validated['actual_cash'] - $expectedCash;

        $reconciliation = CashReconciliation::create([
            'branch_id' => $validated['branch_id'],
            'user_id' => $user->id,
            'date' => $date,
            'expected_cash' => $expectedCash,
            'actual_cash' => $validated['actual_cash'],
            'variance' => $variance,
            'status' => 'pending',
            'notes' => $validated['notes'],
        ]);

        return response()->json([
            'message' => 'Cash reconciliation submitted successfully.',
            'data' => $reconciliation
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $reconciliation = CashReconciliation::findOrFail($id);

        $pin = $request->input('pin');
        if (!$pin) {
            return response()->json(['message' => 'PIN otorisasi Kepala Cabang dibutuhkan!'], 400);
        }

        // Verifikasi PIN
        $managers = \App\Models\User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Admin Cabang', 'Super Admin', 'Kepala Cabang']);
        })->get();

        $authorized = false;
        foreach ($managers as $manager) {
            if ($manager->pos_pin && \Illuminate\Support\Facades\Hash::check($pin, $manager->pos_pin)) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            return response()->json(['message' => 'PIN otorisasi tidak valid!'], 403);
        }

        $validated = $request->validate([
            'actual_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $variance = $validated['actual_cash'] - $reconciliation->expected_cash;

        $reconciliation->update([
            'actual_cash' => $validated['actual_cash'],
            'variance' => $variance,
            'notes' => $validated['notes']
        ]);

        return response()->json([
            'message' => 'Closing harian berhasil diperbarui',
            'data' => $reconciliation
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $reconciliation = CashReconciliation::findOrFail($id);

        $pin = $request->input('pin');
        if (!$pin) {
            return response()->json(['message' => 'PIN otorisasi Kepala Cabang dibutuhkan!'], 400);
        }

        // Verifikasi PIN
        $managers = \App\Models\User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Admin Cabang', 'Super Admin', 'Kepala Cabang']);
        })->get();

        $authorized = false;
        foreach ($managers as $manager) {
            if ($manager->pos_pin && \Illuminate\Support\Facades\Hash::check($pin, $manager->pos_pin)) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            return response()->json(['message' => 'PIN otorisasi tidak valid!'], 403);
        }

        $reconciliation->delete();

        return response()->json(['message' => 'Data closing berhasil dihapus']);
    }
}
