<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PosHeldBill;
use Illuminate\Http\Request;

class PosHeldBillController extends Controller
{
    /**
     * List all held bills for current branch / user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $branchId = $request->query('branch_id') ?: $user->branch_id;

        $query = PosHeldBill::withoutGlobalScopes()
            ->with(['customer:id,name,phone'])
            ->latest();

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });
        } elseif (!$user->hasPermissionTo('manage all') && !$user->hasPermissionTo('*')) {
            $assignedBranchIds = \DB::table('model_has_roles')
                ->where('model_type', get_class($user))
                ->where('model_id', $user->id)
                ->pluck('branch_id')
                ->filter()
                ->unique();

            if ($assignedBranchIds->isNotEmpty()) {
                $query->where(function ($q) use ($assignedBranchIds) {
                    $q->whereIn('branch_id', $assignedBranchIds)
                      ->orWhereNull('branch_id');
                });
            }
        }

        $bills = $query->get();

        return response()->json([
            'data' => $bills,
            'count' => $bills->count(),
        ]);
    }

    /**
     * Hold a new bill / cart
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'subtotal' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'total' => 'nullable|numeric',
            'customer_name' => 'nullable|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'notes' => 'nullable|string|max:500',
            'branch_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        $branchId = $request->input('branch_id') ?: $user->branch_id;

        $bill = PosHeldBill::create([
            'branch_id' => $branchId ?: null,
            'user_id' => $user->id,
            'customer_id' => $request->customer_id,
            'customer_name' => $request->customer_name ?: 'Pelanggan Walk-In',
            'subtotal' => $request->subtotal ?: 0,
            'discount' => $request->discount ?: 0,
            'total' => $request->total ?: 0,
            'items_json' => $request->items,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Transaksi berhasil disimpan sementara (Hold Bill).',
            'bill' => $bill,
        ], 201);
    }

    /**
     * Delete / resume a held bill
     */
    public function destroy($id)
    {
        $bill = PosHeldBill::withoutGlobalScopes()->find($id);
        if ($bill) {
            $bill->delete();
        }

        return response()->json([
            'message' => 'Transaksi ditahan berhasil dihapus/diambil kembali.',
        ]);
    }
}
