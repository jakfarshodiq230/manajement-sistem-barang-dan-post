<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = \App\Models\PurchaseOrder::with(['supplier', 'branch', 'user', 'items.product']);
        
        $search = $request->query('search');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        $page = $request->query('page', 1);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('unreceived') && $request->unreceived == 'true') {
            $query->where('status', 'pending')
                  ->where('approval_status', 'approved')
                  ->whereDoesntHave('goodsReceipt', function($q) {
                      $q->whereNotIn('approval_status', ['rejected', 'cancelled']);
                  });
        }

        if ($request->has('approval_status_filter')) {
            $status = $request->approval_status_filter;
            if ($status == 'need_validation') {
                $query->where(function($q) {
                    $q->whereNull('approval_status')
                      ->orWhereIn('approval_status', ['draft', 'pending']);
                });
            } elseif ($status == 'need_approval') {
                $query->where('approval_status', 'validated');
            } elseif ($status == 'approved') {
                $query->where('approval_status', 'approved');
            }
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereDate('date', '>=', $request->start_date)
                  ->whereDate('date', '<=', $request->end_date);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function($s) use ($search) {
                      $s->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $query->orderBy('created_at', 'desc');

        if ($itemsPerPage == -1) {
            $pos = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $pos = $paginated->items();
        }

        $response = [
            'data' => $pos,
        ];

        if ($paginated) {
            $response['current_page'] = $paginated->currentPage();
            $response['last_page'] = $paginated->lastPage();
            $response['per_page'] = $paginated->perPage();
            $response['total'] = $paginated->total();
        }

        return response()->json($response);
    }

    public function store(Request $request)
    {
        $user = $request->user() ?: auth()->user();
        if ($user && !$user->can('Purchase Order Create') && !$user->can('manage all')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'due_date' => 'nullable|date',
            'invoice_number_supplier' => 'nullable|string|max:255',
            'tax_type' => 'nullable|in:include,exclude,none',
            'tax_percentage' => 'nullable|numeric|min:0',
            'dpp_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'extra_discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            $po_number = 'PO-' . date('YmdHis') . '-' . rand(1000, 9999);
            $taxType = $request->tax_type ?: 'include';
            $taxPercentage = $request->tax_percentage !== null ? (float) $request->tax_percentage : 11.00;
            $extraDiscount = (float) ($request->extra_discount ?? 0);

            $po = \App\Models\PurchaseOrder::create([
                'po_number' => $po_number,
                'invoice_number_supplier' => $request->invoice_number_supplier,
                'branch_id' => $request->branch_id,
                'supplier_id' => $request->supplier_id,
                'user_id' => $request->user()->id,
                'date' => $request->date,
                'due_date' => $request->due_date ?: $request->date,
                'status' => 'pending',
                'approval_status' => 'draft',
                'tax_type' => $taxType,
                'tax_percentage' => $taxPercentage,
                'extra_discount' => $extraDiscount,
                'notes' => $request->notes,
                'subtotal_bruto' => 0,
                'dpp_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
            ]);

            $subtotalBruto = 0;
            $subtotalNetto = 0;

            foreach ($request->items as $item) {
                $qty = (int) ($item['qty'] ?? 1);
                $convQty = max(1, (int) ($item['conversion_qty'] ?? 1));
                $unitName = $item['unit_name'] ?? 'pcs';
                
                $grossPrice = (float) ($item['gross_price'] ?? ($item['unit_cost'] ?? 0));
                $disc1 = (float) ($item['discount_percent_1'] ?? 0);
                $disc2 = (float) ($item['discount_percent_2'] ?? 0);
                $disc3 = (float) ($item['discount_percent_3'] ?? 0);
                $disc4 = (float) ($item['discount_percent_4'] ?? 0);
                $disc5 = (float) ($item['discount_percent_5'] ?? 0);
                $discString = $item['discount_string'] ?? null;
                $discNominal = (float) ($item['discount_amount'] ?? 0);

                // Multi-tier discount calculation (D1 -> D2 -> D3 -> D4 -> D5)
                $priceCurrent = $grossPrice;
                if ($disc1 > 0) $priceCurrent *= (1 - ($disc1 / 100));
                if ($disc2 > 0) $priceCurrent *= (1 - ($disc2 / 100));
                if ($disc3 > 0) $priceCurrent *= (1 - ($disc3 / 100));
                if ($disc4 > 0) $priceCurrent *= (1 - ($disc4 / 100));
                if ($disc5 > 0) $priceCurrent *= (1 - ($disc5 / 100));

                $netUnitPrice = max(0, $priceCurrent - ($discNominal > 0 && $qty > 0 ? ($discNominal / $qty) : 0));
                
                if ($netUnitPrice == 0 && isset($item['unit_cost'])) {
                    $netUnitPrice = (float) $item['unit_cost'];
                }

                $totalLinePrice = (float) ($item['total_price'] ?? ($qty * $netUnitPrice));
                $finalCostPerPiece = ($qty * $convQty) > 0 ? ($totalLinePrice / ($qty * $convQty)) : $netUnitPrice;

                $subtotalBruto += ($qty * $grossPrice);
                $subtotalNetto += $totalLinePrice;

                \App\Models\PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'unit_name' => $unitName,
                    'conversion_qty' => $convQty,
                    'qty' => $qty,
                    'gross_price' => $grossPrice,
                    'discount_percent_1' => $disc1,
                    'discount_percent_2' => $disc2,
                    'discount_percent_3' => $disc3,
                    'discount_percent_4' => $disc4,
                    'discount_percent_5' => $disc5,
                    'discount_string' => $discString,
                    'discount_amount' => $discNominal,
                    'net_unit_price' => $netUnitPrice,
                    'unit_cost' => $netUnitPrice,
                    'total_price' => $totalLinePrice,
                    'final_cost_per_piece' => $finalCostPerPiece,
                ]);
            }

            // Calculate Invoice Summary
            if ($taxType === 'include') {
                $totalAmount = max(0, $subtotalNetto - $extraDiscount);
                $dppAmount = $request->dpp_amount ? (float) $request->dpp_amount : round($totalAmount / (1 + ($taxPercentage / 100)), 2);
                $taxAmount = $request->tax_amount ? (float) $request->tax_amount : round($totalAmount - $dppAmount, 2);
            } elseif ($taxType === 'exclude') {
                $dppAmount = max(0, $subtotalNetto - $extraDiscount);
                $taxAmount = $request->tax_amount ? (float) $request->tax_amount : round($dppAmount * ($taxPercentage / 100), 2);
                $totalAmount = $dppAmount + $taxAmount;
            } else { // none
                $totalAmount = max(0, $subtotalNetto - $extraDiscount);
                $dppAmount = $totalAmount;
                $taxAmount = 0;
            }

            $po->update([
                'subtotal_bruto' => $subtotalBruto,
                'dpp_amount' => $dppAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json(['message' => 'Purchase Order berhasil dibuat', 'po' => $po->load(['supplier', 'branch', 'items.product'])], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal membuat Purchase Order', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $po = \App\Models\PurchaseOrder::with(['supplier', 'branch', 'user', 'items.product'])->findOrFail($id);
        return response()->json($po);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user() ?: auth()->user();
        if ($user && !$user->can('Purchase Order Write') && !$user->can('manage all')) {
            abort(403, 'Unauthorized action.');
        }

        // Typically POs are not fully editable after creation unless they are draft,
        // but we'll provide status update for now (e.g. cancelling).
        $po = \App\Models\PurchaseOrder::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);
        
        $po->update(['status' => $request->status]);
        return response()->json(['message' => 'Status PO berhasil diperbarui', 'po' => $po]);
    }

    public function destroy($id)
    {
        $user = request()->user() ?: auth()->user();
        if ($user && !$user->can('Purchase Order Delete') && !$user->can('manage all')) {
            abort(403, 'Unauthorized action.');
        }

        $po = \App\Models\PurchaseOrder::findOrFail($id);
        if ($po->status === 'completed') {
            return response()->json(['message' => 'PO yang sudah selesai tidak dapat dihapus'], 400);
        }
        $po->delete();
        return response()->json(['message' => 'PO berhasil dihapus']);
    }
}
