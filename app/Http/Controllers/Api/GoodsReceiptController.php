<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GoodsReceiptController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = \App\Models\GoodsReceipt::with([
            'purchaseOrder.branch',
            'purchaseOrder.supplier',
            'user',
            'validator',
            'approver',
            'items.productBranch.product'
        ]);
        
        $search = $request->query('search');
        $status = $request->query('status') ?: $request->query('approval_status');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        $page = $request->query('page', 1);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhere('invoice_number_supplier', 'like', "%{$search}%")
                  ->orWhere('sales_name', 'like', "%{$search}%")
                  ->orWhereHas('purchaseOrder', function($po) use ($search) {
                      $po->where('po_number', 'like', "%{$search}%")
                         ->orWhereHas('supplier', function($s) use ($search) {
                             $s->where('name', 'like', "%{$search}%");
                         });
                  });
            });
        }

        if ($status && $status !== 'all') {
            $query->where('approval_status', $status);
        }
        
        $query->orderBy('created_at', 'desc');

        if ($itemsPerPage == -1) {
            $receipts = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $receipts = $paginated->items();
        }

        $counts = [
            'pending_approval' => \App\Models\GoodsReceipt::where('approval_status', 'pending_approval')->count(),
            'rejected' => \App\Models\GoodsReceipt::where('approval_status', 'rejected')->count(),
            'approved' => \App\Models\GoodsReceipt::where('approval_status', 'approved')->count(),
            'total' => \App\Models\GoodsReceipt::count(),
        ];

        $response = [
            'data' => $receipts,
            'counts' => $counts,
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
        if ($user && !$user->can('Penerimaan Gudang Create') && !$user->can('manage all') && !$user->hasRole('Super Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'date' => 'required|date',
            'sales_name' => 'nullable|string|max:255',
            'received_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'photos' => 'nullable',
        ]);

        // Karena menggunakan FormData, items mungkin dikirim sebagai string JSON
        $itemsData = $request->items;
        if (is_string($itemsData)) {
            $itemsData = json_decode($itemsData, true);
        }

        if (empty($itemsData) || !is_array($itemsData)) {
            return response()->json(['message' => 'Daftar barang tidak valid.'], 400);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            // Find PO to get branch
            $po = \App\Models\PurchaseOrder::findOrFail($request->purchase_order_id);
            
            // Check if PO is already completed
            if ($po->status === 'completed') {
                return response()->json(['message' => 'Purchase Order ini sudah selesai (completed).'], 400);
            }

            $receipt_number = 'GR-' . date('YmdHis') . '-' . rand(1000, 9999);
            
            // Proses Upload Foto Faktur & Fisik
            $photoPaths = [];
            if ($request->hasFile('photos')) {
                $files = $request->file('photos');
                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $photo) {
                    if ($photo && $photo->isValid()) {
                        $path = $photo->store('receipts', 'public');
                        $photoPaths[] = $path;
                    }
                }
            }

            $taxType = $request->tax_type ?: ($po->tax_type ?: 'include');
            $taxPercentage = $request->tax_percentage !== null ? (float) $request->tax_percentage : (float) ($po->tax_percentage ?? 11.00);
            $extraDiscount = (float) ($request->extra_discount ?? ($po->extra_discount ?? 0));

            // Simpan Dokumen Penerimaan Gudang dengan status Menunggu Validasi Kepala Divisi (Pending Approval)
            $gr = \App\Models\GoodsReceipt::create([
                'receipt_number' => $receipt_number,
                'invoice_number_supplier' => $request->invoice_number_supplier ?: $po->invoice_number_supplier,
                'sales_name' => $request->sales_name ?: null,
                'purchase_order_id' => $po->id,
                'user_id' => $user ? $user->id : 1,
                'validated_by' => $user ? $user->id : 1,
                'validated_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'approval_status' => 'pending_approval',
                'date' => $request->date,
                'received_date' => $request->received_date ?: $request->date,
                'due_date' => $request->due_date ?: null,
                'tax_type' => $taxType,
                'tax_percentage' => $taxPercentage,
                'extra_discount' => $extraDiscount,
                'notes' => $request->notes,
                'photos' => $photoPaths,
                'subtotal_bruto' => 0,
                'dpp_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
            ]);

            $subtotalBruto = 0;
            $subtotalNetto = 0;

            foreach ($itemsData as $item) {
                $poItem = null;
                if (!empty($item['purchase_order_item_id'])) {
                    $poItem = \App\Models\PurchaseOrderItem::find($item['purchase_order_item_id']);
                }
                if (!$poItem && !empty($item['product_id'])) {
                    $poItem = \App\Models\PurchaseOrderItem::where('purchase_order_id', $po->id)
                        ->where('product_id', $item['product_id'])
                        ->first();
                }

                $orderedQty = isset($item['ordered_qty']) ? (int) $item['ordered_qty'] : ($poItem ? (int) $poItem->qty : 1);
                $isReceived = isset($item['is_received']) ? filter_var($item['is_received'], FILTER_VALIDATE_BOOLEAN) : (isset($item['qty_received']) && (int)$item['qty_received'] > 0);
                
                $qtyReceived = $isReceived ? max(0, (int) ($item['qty_received'] ?? $orderedQty)) : 0;
                $qtyRejected = max(0, $orderedQty - $qtyReceived);
                
                $rejectionReason = !empty($item['rejection_reason']) ? $item['rejection_reason'] : ($qtyRejected > 0 ? 'Ditolak / retur saat penerimaan fisik gudang' : null);
                $sccCode = !empty($item['scc_code']) ? trim($item['scc_code']) : null;
                $batchNumber = !empty($item['batch_number']) ? trim($item['batch_number']) : null;

                $convQty = max(1, (int) ($item['conversion_qty'] ?? ($poItem ? $poItem->conversion_qty : 1)));
                $unitName = $item['unit_name'] ?? ($poItem ? $poItem->unit_name : 'pcs');

                $grossPrice = isset($item['gross_price']) ? (float) $item['gross_price'] : ($poItem ? (float) $poItem->gross_price : 0);
                $disc1 = isset($item['discount_percent_1']) ? (float) $item['discount_percent_1'] : ($poItem ? (float) $poItem->discount_percent_1 : 0);
                $disc2 = isset($item['discount_percent_2']) ? (float) $item['discount_percent_2'] : ($poItem ? (float) $poItem->discount_percent_2 : 0);
                $disc3 = isset($item['discount_percent_3']) ? (float) $item['discount_percent_3'] : ($poItem ? (float) $poItem->discount_percent_3 : 0);
                $disc4 = isset($item['discount_percent_4']) ? (float) $item['discount_percent_4'] : ($poItem ? (float) $poItem->discount_percent_4 : 0);
                $disc5 = isset($item['discount_percent_5']) ? (float) $item['discount_percent_5'] : ($poItem ? (float) $poItem->discount_percent_5 : 0);
                $discNominal = isset($item['discount_amount']) ? (float) $item['discount_amount'] : ($poItem ? (float) $poItem->discount_amount : 0);
                $discountString = !empty($item['discount_string']) ? $item['discount_string'] : ($poItem ? $poItem->discount_string : null);

                $priceCur = $grossPrice;
                if ($disc1 > 0) $priceCur *= (1 - ($disc1 / 100));
                if ($disc2 > 0) $priceCur *= (1 - ($disc2 / 100));
                if ($disc3 > 0) $priceCur *= (1 - ($disc3 / 100));
                if ($disc4 > 0) $priceCur *= (1 - ($disc4 / 100));
                if ($disc5 > 0) $priceCur *= (1 - ($disc5 / 100));

                $netUnitPrice = max(0, $priceCur - ($discNominal > 0 && $qtyReceived > 0 ? ($discNominal / $qtyReceived) : 0));

                if ($netUnitPrice == 0 && $poItem) {
                    $netUnitPrice = (float) ($poItem->final_cost_per_piece > 0 ? $poItem->final_cost_per_piece : $poItem->unit_cost);
                }

                $totalLinePrice = $qtyReceived * $netUnitPrice;
                $finalCostPerPiece = ($qtyReceived * $convQty) > 0 ? ($totalLinePrice / ($qtyReceived * $convQty)) : $netUnitPrice;

                // Find or create product_branch for this branch and product
                $productId = $item['product_id'] ?? ($poItem ? $poItem->product_id : null);
                $productBranch = \App\Models\ProductBranch::firstOrCreate(
                    ['branch_id' => $po->branch_id, 'product_id' => $productId],
                    ['stock' => 0, 'cost_price' => $finalCostPerPiece, 'price' => 0, 'tax_percentage' => 0]
                );

                $sellingPrice = (!empty($item['price']) && (float)$item['price'] > 0)
                    ? (float)$item['price']
                    : (($productBranch->price > 0) ? (float)$productBranch->price : (float)(ceil(($finalCostPerPiece * 1.25) / 1000) * 1000));

                $minNegoPrice = (!empty($item['min_nego_price']) && (float)$item['min_nego_price'] > 0)
                    ? (float)$item['min_nego_price']
                    : (($productBranch->min_nego_price > 0) ? (float)$productBranch->min_nego_price : (float)(ceil(($finalCostPerPiece * 1.10) / 1000) * 1000));

                // Create GR Item Record (Stok belum bertambah pada tahap ini)
                \App\Models\GoodsReceiptItem::create([
                    'goods_receipt_id' => $gr->id,
                    'purchase_order_item_id' => $poItem ? $poItem->id : ($item['purchase_order_item_id'] ?? null),
                    'product_branch_id' => $productBranch->id,
                    'unit_name' => $unitName,
                    'conversion_qty' => $convQty,
                    'qty_received' => $qtyReceived,
                    'gross_price' => $grossPrice,
                    'discount_percent_1' => $disc1,
                    'discount_percent_2' => $disc2,
                    'discount_percent_3' => $disc3,
                    'discount_percent_4' => $disc4,
                    'discount_percent_5' => $disc5,
                    'discount_string' => $discountString,
                    'discount_amount' => $discNominal,
                    'net_unit_price' => $netUnitPrice,
                    'price' => $sellingPrice,
                    'min_nego_price' => $minNegoPrice,
                    'final_cost_per_piece' => $finalCostPerPiece,
                    'expiration_date' => !empty($item['expiration_date']) ? $item['expiration_date'] : null,
                    'batch_number' => $batchNumber,
                    'scc_code' => $sccCode,
                    'is_received' => $isReceived,
                    'qty_rejected' => $qtyRejected,
                    'rejection_reason' => $rejectionReason,
                ]);

                if ($isReceived && $qtyReceived > 0) {
                    $subtotalBruto += ($qtyReceived * $grossPrice);
                    $subtotalNetto += $totalLinePrice;
                }
            }

            if ($taxType === 'include') {
                $totalAmount = max(0, $subtotalNetto - $extraDiscount);
                $dppAmount = $request->dpp_amount ? (float) $request->dpp_amount : round($totalAmount / (1 + ($taxPercentage / 100)), 2);
                $taxAmount = $request->tax_amount ? (float) $request->tax_amount : round($totalAmount - $dppAmount, 2);
            } elseif ($taxType === 'exclude') {
                $dppAmount = max(0, $subtotalNetto - $extraDiscount);
                $taxAmount = $request->tax_amount ? (float) $request->tax_amount : round($dppAmount * ($taxPercentage / 100), 2);
                $totalAmount = $dppAmount + $taxAmount;
            } else {
                $totalAmount = max(0, $subtotalNetto - $extraDiscount);
                $dppAmount = $totalAmount;
                $taxAmount = 0;
            }

            $gr->update([
                'subtotal_bruto' => $subtotalBruto,
                'dpp_amount' => $dppAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => 'Faktur & ceklis fisik barang berhasil disimpan oleh staf gudang dan diajukan ke Kepala Divisi untuk diverifikasi.',
                'gr' => $gr
            ], 201);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Log::error('GoodsReceipt Store Error: ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine());
            return response()->json(['message' => 'Gagal memproses Penerimaan Barang: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Revisi Dokumen Penerimaan Barang yang Ditolak oleh Petugas Gudang
     */
    public function update(Request $request, $id)
    {
        $user = $request->user() ?: auth()->user();
        $gr = \App\Models\GoodsReceipt::with('purchaseOrder')->findOrFail($id);

        if ($gr->approval_status === 'approved') {
            return response()->json(['message' => 'Dokumen yang sudah disetujui tidak dapat direvisi.'], 400);
        }

        $request->validate([
            'invoice_number_supplier' => 'nullable|string|max:255',
            'sales_name' => 'nullable|string|max:255',
            'received_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'tax_type' => 'nullable|in:include,exclude,none',
            'tax_percentage' => 'nullable|numeric|min:0',
            'extra_discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required',
        ]);

        $itemsData = $request->items;
        if (is_string($itemsData)) {
            $itemsData = json_decode($itemsData, true);
        }

        if (empty($itemsData) || !is_array($itemsData)) {
            return response()->json(['message' => 'Daftar barang tidak valid.'], 400);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            $taxType = $request->tax_type ?: ($gr->tax_type ?: 'include');
            $taxPercentage = $request->tax_percentage !== null ? (float) $request->tax_percentage : (float) ($gr->tax_percentage ?? 11.00);
            $extraDiscount = (float) ($request->extra_discount ?? ($gr->extra_discount ?? 0));

            $photoUrls = $gr->photos ?: [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photoFile) {
                    $path = $photoFile->store('goods_receipts', 'public');
                    $photoUrls[] = '/storage/' . $path;
                }
            }

            $gr->update([
                'invoice_number_supplier' => $request->invoice_number_supplier ?? $gr->invoice_number_supplier,
                'sales_name' => $request->sales_name ?? $gr->sales_name,
                'received_date' => $request->received_date ?? $gr->received_date,
                'due_date' => $request->due_date ?? $gr->due_date,
                'tax_type' => $taxType,
                'tax_percentage' => $taxPercentage,
                'extra_discount' => $extraDiscount,
                'notes' => $request->notes ?? $gr->notes,
                'photos' => $photoUrls,
                'approval_status' => 'pending_approval', // Reset ke pending approval setelah revisi
                'rejection_reason' => null, // Reset alasan penolakan
                'validated_by' => $user ? $user->id : $gr->validated_by,
                'validated_at' => now(),
            ]);

            \App\Models\GoodsReceiptItem::where('goods_receipt_id', $gr->id)->delete();

            $subtotalBruto = 0;
            $subtotalNetto = 0;
            $po = $gr->purchaseOrder;

            foreach ($itemsData as $item) {
                $poItem = null;
                if (!empty($item['purchase_order_item_id'])) {
                    $poItem = \App\Models\PurchaseOrderItem::find($item['purchase_order_item_id']);
                }
                if (!$poItem && !empty($item['product_id']) && $po) {
                    $poItem = \App\Models\PurchaseOrderItem::where('purchase_order_id', $po->id)
                        ->where('product_id', $item['product_id'])
                        ->first();
                }
                if (!$poItem && $po && !empty($item['product_id'])) {
                    $poItem = \App\Models\PurchaseOrderItem::firstOrCreate(
                        ['purchase_order_id' => $po->id, 'product_id' => $item['product_id']],
                        [
                            'qty' => $item['ordered_qty'] ?? 1,
                            'unit_name' => $item['unit_name'] ?? 'pcs',
                            'conversion_qty' => $item['conversion_qty'] ?? 1,
                            'unit_cost' => $item['gross_price'] ?? 0,
                            'total_price' => ($item['ordered_qty'] ?? 1) * ($item['gross_price'] ?? 0)
                        ]
                    );
                }

                $orderedQty = isset($item['ordered_qty']) ? (int) $item['ordered_qty'] : ($poItem ? (int) $poItem->qty : 1);
                $isReceived = isset($item['is_received']) ? filter_var($item['is_received'], FILTER_VALIDATE_BOOLEAN) : (isset($item['qty_received']) && (int)$item['qty_received'] > 0);
                
                $qtyReceived = $isReceived ? max(0, (int) ($item['qty_received'] ?? $orderedQty)) : 0;
                $qtyRejected = max(0, $orderedQty - $qtyReceived);
                
                $rejectionReason = !empty($item['rejection_reason']) ? $item['rejection_reason'] : ($qtyRejected > 0 ? 'Ditolak / retur saat penerimaan fisik gudang' : null);
                $sccCode = !empty($item['scc_code']) ? trim($item['scc_code']) : null;
                $batchNumber = !empty($item['batch_number']) ? trim($item['batch_number']) : null;

                $convQty = max(1, (int) ($item['conversion_qty'] ?? ($poItem ? $poItem->conversion_qty : 1)));
                $unitName = $item['unit_name'] ?? ($poItem ? $poItem->unit_name : 'pcs');

                $grossPrice = isset($item['gross_price']) ? (float) $item['gross_price'] : ($poItem ? (float) $poItem->gross_price : 0);
                $disc1 = isset($item['discount_percent_1']) ? (float) $item['discount_percent_1'] : ($poItem ? (float) $poItem->discount_percent_1 : 0);
                $disc2 = isset($item['discount_percent_2']) ? (float) $item['discount_percent_2'] : ($poItem ? (float) $poItem->discount_percent_2 : 0);
                $disc3 = isset($item['discount_percent_3']) ? (float) $item['discount_percent_3'] : ($poItem ? (float) $poItem->discount_percent_3 : 0);
                $disc4 = isset($item['discount_percent_4']) ? (float) $item['discount_percent_4'] : ($poItem ? (float) $poItem->discount_percent_4 : 0);
                $disc5 = isset($item['discount_percent_5']) ? (float) $item['discount_percent_5'] : ($poItem ? (float) $poItem->discount_percent_5 : 0);
                $discNominal = isset($item['discount_amount']) ? (float) $item['discount_amount'] : ($poItem ? (float) $poItem->discount_amount : 0);
                $discountString = !empty($item['discount_string']) ? $item['discount_string'] : ($poItem ? $poItem->discount_string : null);

                $priceCur = $grossPrice;
                if ($disc1 > 0) $priceCur *= (1 - ($disc1 / 100));
                if ($disc2 > 0) $priceCur *= (1 - ($disc2 / 100));
                if ($disc3 > 0) $priceCur *= (1 - ($disc3 / 100));
                if ($disc4 > 0) $priceCur *= (1 - ($disc4 / 100));
                if ($disc5 > 0) $priceCur *= (1 - ($disc5 / 100));

                $netUnitPrice = max(0, $priceCur - ($discNominal > 0 && $qtyReceived > 0 ? ($discNominal / $qtyReceived) : 0));

                if ($netUnitPrice == 0 && $poItem) {
                    $netUnitPrice = (float) ($poItem->final_cost_per_piece > 0 ? $poItem->final_cost_per_piece : $poItem->unit_cost);
                }

                $totalLinePrice = $qtyReceived * $netUnitPrice;
                $finalCostPerPiece = ($qtyReceived * $convQty) > 0 ? ($totalLinePrice / ($qtyReceived * $convQty)) : $netUnitPrice;

                $productId = $item['product_id'] ?? ($poItem ? $poItem->product_id : null);
                $productBranch = \App\Models\ProductBranch::firstOrCreate(
                    ['branch_id' => $po ? $po->branch_id : 1, 'product_id' => $productId],
                    ['stock' => 0, 'cost_price' => $finalCostPerPiece, 'price' => 0, 'tax_percentage' => 0]
                );

                $sellingPrice = (!empty($item['price']) && (float)$item['price'] > 0)
                    ? (float)$item['price']
                    : (($productBranch->price > 0) ? (float)$productBranch->price : (float)(ceil(($finalCostPerPiece * 1.25) / 1000) * 1000));

                $minNegoPrice = (!empty($item['min_nego_price']) && (float)$item['min_nego_price'] > 0)
                    ? (float)$item['min_nego_price']
                    : (($productBranch->min_nego_price > 0) ? (float)$productBranch->min_nego_price : (float)(ceil(($finalCostPerPiece * 1.10) / 1000) * 1000));

                \App\Models\GoodsReceiptItem::create([
                    'goods_receipt_id' => $gr->id,
                    'purchase_order_item_id' => $poItem ? $poItem->id : ($item['purchase_order_item_id'] ?? null),
                    'product_branch_id' => $productBranch->id,
                    'unit_name' => $unitName,
                    'conversion_qty' => $convQty,
                    'qty_received' => $qtyReceived,
                    'gross_price' => $grossPrice,
                    'discount_percent_1' => $disc1,
                    'discount_percent_2' => $disc2,
                    'discount_percent_3' => $disc3,
                    'discount_percent_4' => $disc4,
                    'discount_percent_5' => $disc5,
                    'discount_string' => $discountString,
                    'discount_amount' => $discNominal,
                    'net_unit_price' => $netUnitPrice,
                    'price' => $sellingPrice,
                    'min_nego_price' => $minNegoPrice,
                    'final_cost_per_piece' => $finalCostPerPiece,
                    'expiration_date' => !empty($item['expiration_date']) ? $item['expiration_date'] : null,
                    'batch_number' => $batchNumber,
                    'scc_code' => $sccCode,
                    'is_received' => $isReceived,
                    'qty_rejected' => $qtyRejected,
                    'rejection_reason' => $rejectionReason,
                ]);

                if ($isReceived && $qtyReceived > 0) {
                    $subtotalBruto += ($qtyReceived * $grossPrice);
                    $subtotalNetto += $totalLinePrice;
                }
            }

            if ($taxType === 'include') {
                $totalAmount = max(0, $subtotalNetto - $extraDiscount);
                $dppAmount = $request->dpp_amount ? (float) $request->dpp_amount : round($totalAmount / (1 + ($taxPercentage / 100)), 2);
                $taxAmount = $request->tax_amount ? (float) $request->tax_amount : round($totalAmount - $dppAmount, 2);
            } elseif ($taxType === 'exclude') {
                $dppAmount = max(0, $subtotalNetto - $extraDiscount);
                $taxAmount = $request->tax_amount ? (float) $request->tax_amount : round($dppAmount * ($taxPercentage / 100), 2);
                $totalAmount = $dppAmount + $taxAmount;
            } else {
                $totalAmount = max(0, $subtotalNetto - $extraDiscount);
                $dppAmount = $totalAmount;
                $taxAmount = 0;
            }

            $gr->update([
                'subtotal_bruto' => $subtotalBruto,
                'dpp_amount' => $dppAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => 'Revisi dokumen penerimaan berhasil disimpan dan diajukan ulang ke Kepala Divisi!',
                'gr' => $gr
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal memperbarui penerimaan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Persetujuan / Validasi Final Penerimaan Barang oleh Kepala Divisi
     * Saat disetujui, stok fisik bertambah, batch & SCC dibuat, dan harga jual/nego diterapkan.
     */
    public function approve(Request $request, $id)
    {
        $user = $request->user() ?: auth()->user();
        $gr = \App\Models\GoodsReceipt::with(['purchaseOrder', 'items.productBranch.product'])->findOrFail($id);

        if ($gr->approval_status === 'approved') {
            return response()->json(['message' => 'Penerimaan barang ini sudah disetujui sebelumnya.'], 400);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            $po = $gr->purchaseOrder;

            $gr->update([
                'approval_status' => 'approved',
                'approved_by' => $user ? $user->id : 1,
                'approved_at' => now(),
            ]);

            $rejectedItemsList = [];

            // Eksekusi penambahan stok fisik cabang, pembuatan batch/SCC, dan pencatatan riwayat mutasi
            foreach ($gr->items as $grItem) {
                $productBranch = $grItem->productBranch;
                if (!$productBranch && $po) {
                    $productBranch = \App\Models\ProductBranch::firstOrCreate(
                        ['branch_id' => $po->branch_id, 'product_id' => $grItem->product_id],
                        ['stock' => 0, 'cost_price' => $grItem->final_cost_per_piece, 'price' => $grItem->price, 'tax_percentage' => 0]
                    );
                }

                if ($grItem->is_received && $grItem->qty_received > 0 && $productBranch) {
                    $stockNotes = 'Penerimaan Barang Disetujui Kepala Divisi (PO: ' . ($po ? $po->po_number : '-') . ')';
                    if ($grItem->scc_code) {
                        $stockNotes .= ' [SCC: ' . $grItem->scc_code . ']';
                    }
                    if ($grItem->batch_number) {
                        $stockNotes .= ' [Batch: ' . $grItem->batch_number . ']';
                    }

                    // 1. Tambah Riwayat Pergerakan Stok (Stock Movement IN)
                    \App\Models\StockMovement::create([
                        'product_branch_id' => $productBranch->id,
                        'user_id' => $user ? $user->id : 1,
                        'type' => 'in',
                        'quantity' => $grItem->qty_received,
                        'unit_cost' => $grItem->final_cost_per_piece,
                        'reference_type' => 'goods_receipt',
                        'reference_id' => $gr->id,
                        'notes' => $stockNotes,
                    ]);

                    $sellingPrice = ($grItem->price > 0)
                        ? (float)$grItem->price
                        : (($productBranch->price > 0) ? (float)$productBranch->price : (float)(ceil(($grItem->final_cost_per_piece * 1.25) / 1000) * 1000));

                    $minNegoPrice = ($grItem->min_nego_price > 0)
                        ? (float)$grItem->min_nego_price
                        : (($productBranch->min_nego_price > 0) ? (float)$productBranch->min_nego_price : (float)(ceil(($grItem->final_cost_per_piece * 1.10) / 1000) * 1000));

                    // 2. Tambah Stok Fisik Cabang & Perbarui 3 Tingkatan Harga di Master Cabang
                    $productBranch->increment('stock', $grItem->qty_received);
                    $productBranch->update([
                        'cost_price' => $grItem->final_cost_per_piece > 0 ? $grItem->final_cost_per_piece : $productBranch->cost_price,
                        'price' => $sellingPrice > 0 ? $sellingPrice : $productBranch->price,
                        'min_nego_price' => $minNegoPrice > 0 ? $minNegoPrice : $productBranch->min_nego_price,
                    ]);

                    // 3. Simpan ke Tabel Batch & SCC Produk (Product Batches) dengan 3 Tingkatan Harga Lengkap
                    \App\Models\ProductBatch::create([
                        'product_branch_id' => $productBranch->id,
                        'batch_number' => $grItem->batch_number,
                        'scc_code' => $grItem->scc_code,
                        'qty' => $grItem->qty_received,
                        'cost_price' => $grItem->final_cost_per_piece,
                        'price' => $sellingPrice,
                        'min_nego_price' => $minNegoPrice,
                        'entry_date' => $gr->received_date ?: $gr->date,
                        'expiration_date' => $grItem->expiration_date,
                    ]);
                }

                if ($grItem->qty_rejected > 0 && $productBranch) {
                    $rejectedItemsList[] = [
                        'product_branch_id' => $productBranch->id,
                        'qty' => $grItem->qty_rejected,
                        'unit_price' => $grItem->net_unit_price > 0 ? $grItem->net_unit_price : $grItem->gross_price,
                        'reason' => $grItem->rejection_reason,
                    ];
                }
            }

            // Buat Retur Otomatis jika ada barang yang ditolak / rusak
            if (!empty($rejectedItemsList) && $po) {
                $datePrefix = date('Ymd');
                $lastReturn = \App\Models\ReturnTransaction::where('return_number', 'like', "RET-{$datePrefix}-%")->orderBy('id', 'desc')->first();
                $nextSeq = 1;
                if ($lastReturn) {
                    $lastSeq = (int) substr($lastReturn->return_number, -4);
                    $nextSeq = $lastSeq + 1;
                }
                $returnNumber = "RET-{$datePrefix}-" . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

                $reasonsSummary = implode('; ', array_unique(array_filter(array_column($rejectedItemsList, 'reason'))));
                $notes = "Retur Otomatis Penerimaan (Penerimaan: {$gr->receipt_number}, PO: {$po->po_number}). " . ($reasonsSummary ? "Alasan: {$reasonsSummary}" : '');

                $totalReturnAmount = 0;
                foreach ($rejectedItemsList as $rej) {
                    $totalReturnAmount += ($rej['qty'] * $rej['unit_price']);
                }

                $returnTx = \App\Models\ReturnTransaction::create([
                    'return_number' => $returnNumber,
                    'branch_id' => $po->branch_id,
                    'user_id' => $user ? $user->id : 1,
                    'reference_type' => 'purchase',
                    'reference_id' => $po->id,
                    'return_type' => 'tukar_barang',
                    'status' => 'pending',
                    'approval_status' => 'approved',
                    'total_amount' => $totalReturnAmount,
                    'notes' => $notes,
                ]);

                foreach ($rejectedItemsList as $rej) {
                    \App\Models\ReturnItem::create([
                        'return_transaction_id' => $returnTx->id,
                        'product_branch_id' => $rej['product_branch_id'],
                        'qty' => $rej['qty'],
                        'unit_price' => $rej['unit_price'],
                        'subtotal' => $rej['qty'] * $rej['unit_price'],
                    ]);
                }
            }

            // Selesaikan status PO
            if ($po) {
                $po->update(['status' => 'completed']);
            }

            // Catat atau Sinkronkan ke Buku Hutang Supplier (Accounts Payable)
            if ($gr->total_amount > 0) {
                $existingPayable = \App\Models\Payable::where('goods_receipt_id', $gr->id)->first();
                if (!$existingPayable) {
                    \App\Models\Payable::create([
                        'payable_number' => 'AP-' . date('Ymd') . '-' . rand(1000, 9999),
                        'purchase_order_id' => $po ? $po->id : null,
                        'goods_receipt_id' => $gr->id,
                        'supplier_id' => $po ? $po->supplier_id : 1,
                        'branch_id' => $po ? $po->branch_id : 1,
                        'invoice_number_supplier' => $gr->invoice_number_supplier ?: ($po ? $po->invoice_number_supplier : null),
                        'invoice_date' => $gr->date ?: ($po ? $po->date : now()),
                        'due_date' => $gr->due_date ?: ($po ? $po->due_date : null),
                        'total_amount' => (float)$gr->total_amount,
                        'paid_amount' => 0,
                        'remaining_amount' => (float)$gr->total_amount,
                        'status' => 'unpaid',
                        'notes' => $gr->notes ?: ($po ? $po->notes : null),
                        'created_by' => $user ? $user->id : 1,
                    ]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => 'Penerimaan barang berhasil divalidasi dan disetujui! Stok fisik cabang dan nomor batch telah bertambah.',
                'gr' => $gr->fresh(['purchaseOrder', 'items.productBranch.product', 'user', 'approver', 'validator'])
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Log::error('GoodsReceipt Approval Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menyetujui penerimaan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Penolakan Penerimaan Barang oleh Kepala Divisi (Minta Revisi)
     */
    public function reject(Request $request, $id)
    {
        $user = $request->user() ?: auth()->user();
        $gr = \App\Models\GoodsReceipt::findOrFail($id);

        $request->validate([
            'reason' => 'nullable|string',
        ]);

        $gr->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $request->reason ?: 'Ditolak / diminta revisi oleh Kepala Divisi',
            'approved_by' => $user ? $user->id : 1,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Penerimaan barang telah ditolak / dikembalikan untuk revisi.',
            'gr' => $gr
        ]);
    }

    public function show($id)
    {
        $gr = \App\Models\GoodsReceipt::with([
            'purchaseOrder.supplier',
            'purchaseOrder.branch',
            'purchaseOrder.items.product',
            'user.employee',
            'validator.employee',
            'approver.employee',
            'items.productBranch.product'
        ])->findOrFail($id);

        return response()->json($gr);
    }

    public function destroy($id)
    {
        if (!request()->user()->can('Penerimaan Gudang Delete')) {
            abort(403, 'Unauthorized action.');
        }

        $gr = \App\Models\GoodsReceipt::with(['items.productBranch.product'])->findOrFail($id);
        
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            if ($gr->approval_status === 'approved') {
                // Reverse stock using FIFO
                foreach ($gr->items as $item) {
                    $pb = $item->productBranch;
                    if ($pb && $item->qty_received > 0) {
                        $qtyToDeduct = $item->qty_received;
                        $stockMethod = $pb->product->stock_method ?? 'fifo';
                        
                        $batchQuery = \App\Models\ProductBatch::where('product_branch_id', $pb->id)->where('qty', '>', 0);
                            
                        if ($stockMethod === 'fefo') {
                            $batchQuery->orderByRaw('expiration_date IS NULL, expiration_date ASC, entry_date ASC');
                        } elseif ($stockMethod === 'lifo') {
                            $batchQuery->orderBy('entry_date', 'desc')->orderBy('id', 'desc');
                        } else { // fifo
                            $batchQuery->orderBy('entry_date', 'asc')->orderBy('id', 'asc');
                        }
                        
                        $batches = $batchQuery->lockForUpdate()->get();
                        
                        foreach ($batches as $batch) {
                            if ($qtyToDeduct <= 0) break;
                            
                            if ($batch->qty >= $qtyToDeduct) {
                                $batch->decrement('qty', $qtyToDeduct);
                                $qtyToDeduct = 0;
                            } else {
                                $qtyToDeduct -= $batch->qty;
                                $batch->update(['qty' => 0]);
                            }
                        }

                        if ($qtyToDeduct > 0) {
                             throw new \Exception("Stok Batch tidak mencukupi untuk membatalkan Penerimaan Gudang ini (Sisa kurang: {$qtyToDeduct})");
                        }
                        
                        $pb->decrement('stock', $item->qty_received);
                        
                        \App\Models\StockMovement::create([
                            'product_branch_id' => $pb->id,
                            'type' => 'out',
                            'quantity' => $item->qty_received,
                            'unit_cost' => $pb->cost_price,
                            'notes' => "Pembatalan Penerimaan Gudang (GR): {$gr->receipt_number}",
                            'user_id' => request()->user()->id,
                            'reference_type' => \App\Models\GoodsReceipt::class,
                            'reference_id' => $gr->id
                        ]);
                    }
                }
            }

            // Restore PO status
            if ($gr->purchase_order_id) {
                $po = \App\Models\PurchaseOrder::find($gr->purchase_order_id);
                if ($po) {
                    $po->update(['status' => 'pending']);
                }
            }

            $gr->delete();
            \Illuminate\Support\Facades\DB::commit();

            return response()->json(['message' => 'Penerimaan Barang berhasil dihapus dan stok telah dibatalkan']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal menghapus penerimaan barang', 'error' => $e->getMessage()], 400);
        }
    }
}
