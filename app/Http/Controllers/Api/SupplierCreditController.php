<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupplierCredit;
use Illuminate\Http\Request;

class SupplierCreditController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierCredit::with(['supplier', 'branch', 'returnTransaction', 'purchaseOrder', 'creator']);

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('available_only') && filter_var($request->available_only, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('remaining_amount', '>', 0)->whereIn('status', ['available', 'partially_used']);
        }

        $credits = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $credits,
            'total_available_credit' => (float) $credits->where('status', '!=', 'used')->sum('remaining_amount'),
        ]);
    }

    public function show($id)
    {
        $credit = SupplierCredit::with([
            'supplier',
            'branch',
            'returnTransaction.items.productBranch.product',
            'purchaseOrder',
            'creator'
        ])->findOrFail($id);

        return response()->json($credit);
    }
}
