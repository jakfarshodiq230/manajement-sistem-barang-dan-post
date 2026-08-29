<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PettyCash;
use App\Models\CashShift;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PettyCashController extends Controller
{
    /**
     * List petty cash entries with filtering and summary
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isGlobalUser = $user && ($user->can('manage all') || $user->can('Kasir (POS) Approve') || $user->can('Audit & Laporan Read') || !$user->branch_id);
        $branchId = $request->query('branch_id');
        
        // If not requested explicitly and not global admin, lock to user's assigned branch
        if (!$branchId && !$isGlobalUser && $user && $user->branch_id) {
            $branchId = $user->branch_id;
        }

        $category = $request->query('category');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $q = $request->query('q');

        $query = PettyCash::with(['user:id,name', 'branch:id,name'])->latest('date');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('description', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%");
            });
        }

        $totalAmount = (clone $query)->sum('amount');
        $items = $query->paginate($request->query('itemsPerPage', 15));

        $defaultCategories = [
            'Operasional Toko',
            'Listrik & PLN',
            'Air & Galon Minum',
            'Bensin & Transportasi Kurir',
            'ATK & Kertas Thermal',
            'Konsumsi & Snack Lembur',
            'Kebersihan & Perlengkapan',
            'Lain-lain',
        ];
        $dbCategories = PettyCash::distinct()->pluck('category')->filter()->values()->all();
        $allCategories = array_values(array_unique(array_merge($defaultCategories, $dbCategories)));

        return response()->json([
            'data' => $items->items(),
            'total' => $items->total(),
            'totalAmount' => (float) $totalAmount,
            'currentPage' => $items->currentPage(),
            'lastPage' => $items->lastPage(),
            'categories' => $allCategories,
        ]);
    }

    /**
     * Store a new petty cash record
     */
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'date' => 'required|date',
            'receipt_image' => 'nullable|string',
            'branch_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        $targetBranchId = $request->branch_id ?: ($user->branch_id ?: 1);

        // Check if there is an active open cash shift to link with
        $activeShift = CashShift::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        $pettyCash = PettyCash::create([
            'branch_id' => $targetBranchId,
            'user_id' => $user->id,
            'cash_shift_id' => $activeShift ? $activeShift->id : null,
            'category' => $request->category,
            'amount' => $request->amount,
            'description' => $request->description,
            'receipt_image' => $request->receipt_image,
            'date' => $request->date,
        ]);

        return response()->json([
            'message' => 'Pengeluaran kas kecil berhasil dicatat.',
            'data' => $pettyCash,
        ], 201);
    }

    /**
     * Update petty cash record
     */
    public function update(Request $request, $id)
    {
        $pettyCash = PettyCash::findOrFail($id);

        $request->validate([
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'date' => 'required|date',
            'receipt_image' => 'nullable|string',
        ]);

        $pettyCash->update($request->only([
            'category', 'amount', 'description', 'date', 'receipt_image'
        ]));

        return response()->json([
            'message' => 'Pengeluaran kas kecil berhasil diperbarui.',
            'data' => $pettyCash,
        ]);
    }

    /**
     * Delete petty cash record
     */
    public function destroy($id)
    {
        $pettyCash = PettyCash::findOrFail($id);
        $pettyCash->delete();

        return response()->json([
            'message' => 'Catatan kas kecil berhasil dihapus.',
        ]);
    }
}
