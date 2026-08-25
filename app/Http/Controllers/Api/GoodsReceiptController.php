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
        $query = \App\Models\GoodsReceipt::with(['purchaseOrder.branch', 'purchaseOrder.supplier', 'user', 'items.productBranch.product']);
        
        $search = $request->query('search');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        $page = $request->query('page', 1);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhereHas('purchaseOrder', function($po) use ($search) {
                      $po->where('po_number', 'like', "%{$search}%")
                         ->orWhereHas('supplier', function($s) use ($search) {
                             $s->where('name', 'like', "%{$search}%");
                         });
                  });
            });
        }
        
        $query->orderBy('created_at', 'desc');

        if ($itemsPerPage == -1) {
            $receipts = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $receipts = $paginated->items();
        }

        $response = [
            'data' => $receipts,
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
        if (!request()->user()->can('Penerimaan Gudang Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'photos' => 'required|array|min:1', // Wajib ada foto
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120', // Maks 5MB per file
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
            
            // Proses Upload Foto
            $photoPaths = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('receipts', 'public');
                    $photoPaths[] = $path;
                }
            }

            $taxType = $request->tax_type ?: ($po->tax_type ?: 'include');
            $taxPercentage = $request->tax_percentage !== null ? (float) $request->tax_percentage : (float) ($po->tax_percentage ?? 11.00);
            $extraDiscount = (float) ($request->extra_discount ?? ($po->extra_discount ?? 0));

            $gr = \App\Models\GoodsReceipt::create([
                'receipt_number' => $receipt_number,
                'invoice_number_supplier' => $request->invoice_number_supplier ?: $po->invoice_number_supplier,
                'purchase_order_id' => $po->id,
                'user_id' => $request->user()->id,
                'date' => $request->date,
                'tax_type' => $taxType,
                'tax_percentage' => $taxPercentage,
                'extra_discount' => $extraDiscount,
                'notes' => $request->notes,
                'photos' => $photoPaths,
                'approval_status' => 'draft',
                'subtotal_bruto' => 0,
                'dpp_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
            ]);

            $subtotalBruto = 0;
            $subtotalNetto = 0;

            foreach ($itemsData as $item) {
                if ($item['qty_received'] > 0) {
                    $qtyReceived = (int) $item['qty_received'];
                    $convQty = max(1, (int) ($item['conversion_qty'] ?? 1));
                    $unitName = $item['unit_name'] ?? 'pcs';

                    $poItem = \App\Models\PurchaseOrderItem::find($item['purchase_order_item_id']);
                    $grossPrice = isset($item['gross_price']) ? (float) $item['gross_price'] : ($poItem ? (float) $poItem->gross_price : 0);
                    $disc1 = isset($item['discount_percent_1']) ? (float) $item['discount_percent_1'] : ($poItem ? (float) $poItem->discount_percent_1 : 0);
                    $disc2 = isset($item['discount_percent_2']) ? (float) $item['discount_percent_2'] : ($poItem ? (float) $poItem->discount_percent_2 : 0);
                    $discNominal = isset($item['discount_amount']) ? (float) $item['discount_amount'] : ($poItem ? (float) $poItem->discount_amount : 0);

                    $priceAfterD1 = $grossPrice * (1 - ($disc1 / 100));
                    $priceAfterD2 = $priceAfterD1 * (1 - ($disc2 / 100));
                    $netUnitPrice = max(0, $priceAfterD2 - ($discNominal > 0 && $qtyReceived > 0 ? ($discNominal / $qtyReceived) : 0));

                    if ($netUnitPrice == 0 && $poItem) {
                        $netUnitPrice = (float) $poItem->unit_cost;
                    }

                    $totalLinePrice = $qtyReceived * $netUnitPrice;
                    $finalCostPerPiece = ($qtyReceived * $convQty) > 0 ? ($totalLinePrice / ($qtyReceived * $convQty)) : $netUnitPrice;

                    $subtotalBruto += ($qtyReceived * $grossPrice);
                    $subtotalNetto += $totalLinePrice;

                    // Find or create product_branch for this branch and product
                    $productBranch = \App\Models\ProductBranch::firstOrCreate(
                        ['branch_id' => $po->branch_id, 'product_id' => $item['product_id']],
                        ['stock' => 0, 'cost_price' => $finalCostPerPiece, 'price' => 0, 'tax_percentage' => 0]
                    );

                    // Create GR Item without adding stock
                    \App\Models\GoodsReceiptItem::create([
                        'goods_receipt_id' => $gr->id,
                        'purchase_order_item_id' => $item['purchase_order_item_id'],
                        'product_branch_id' => $productBranch->id,
                        'unit_name' => $unitName,
                        'conversion_qty' => $convQty,
                        'qty_received' => $qtyReceived,
                        'gross_price' => $grossPrice,
                        'discount_percent_1' => $disc1,
                        'discount_percent_2' => $disc2,
                        'discount_amount' => $discNominal,
                        'net_unit_price' => $netUnitPrice,
                        'price' => !empty($item['price']) ? $item['price'] : $productBranch->price,
                        'min_nego_price' => !empty($item['min_nego_price']) ? $item['min_nego_price'] : $productBranch->min_nego_price,
                        'final_cost_per_piece' => $finalCostPerPiece,
                        'expiration_date' => !empty($item['expiration_date']) ? $item['expiration_date'] : null,
                    ]);
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

            return response()->json(['message' => 'Penerimaan Barang berhasil disimpan sebagai draft.', 'gr' => $gr], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal memproses Penerimaan Barang', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $gr = \App\Models\GoodsReceipt::with(['purchaseOrder.supplier', 'purchaseOrder.branch', 'purchaseOrder.items.product', 'user.employee', 'items.productBranch.product'])->findOrFail($id);
        return response()->json($gr);
    }

    public function update(Request $request, $id)
    {
        if (!request()->user()->can('Penerimaan Gudang Write')) {
            abort(403, 'Unauthorized action.');
        }

        $gr = \App\Models\GoodsReceipt::with('items')->findOrFail($id);

        $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
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
            // Proses Tambahan Foto (Opsional untuk Edit)
            $photoPaths = $gr->photos ?? [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('receipts', 'public');
                    $photoPaths[] = $path; // Tambahkan foto baru ke yang lama
                }
            }

            // Update GR info
            $gr->update([
                'date' => $request->date,
                'notes' => $request->notes,
                'photos' => $photoPaths,
                'approval_status' => 'draft',
                'rejection_reason' => null,
                'approved_by' => null,
                'approved_at' => null,
                'validated_by' => null,
                'validated_at' => null,
            ]);

            // We can delete old items and recreate, or update existing.
            // Deleting and recreating is cleaner for sync.
            $gr->items()->delete();

            foreach ($itemsData as $item) {
                if ($item['qty_received'] > 0) {
                    // Find or create product_branch for this branch and product
                    $productBranch = \App\Models\ProductBranch::firstOrCreate(
                        ['branch_id' => $gr->purchaseOrder->branch_id, 'product_id' => $item['product_id']],
                        ['stock' => 0, 'cost_price' => 0, 'price' => 0, 'tax_percentage' => 0]
                    );

                    \App\Models\GoodsReceiptItem::create([
                        'goods_receipt_id' => $gr->id,
                        'purchase_order_item_id' => $item['purchase_order_item_id'],
                        'product_branch_id' => $productBranch->id,
                        'qty_received' => $item['qty_received'],
                        'price' => $item['price'] ?? 0,
                        'min_nego_price' => $item['min_nego_price'] ?? 0,
                        'expiration_date' => $item['expiration_date'] ?? null,
                    ]);
                }
            }

            // If all items are 0, maybe we should reject? Or allow empty GR?
            // The validation doesn't prevent all 0s, but frontend will.

            \Illuminate\Support\Facades\DB::commit();

            return response()->json(['message' => 'Penerimaan barang berhasil direvisi', 'goods_receipt' => $gr]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal merevisi penerimaan barang', 'error' => $e->getMessage()], 500);
        }
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
