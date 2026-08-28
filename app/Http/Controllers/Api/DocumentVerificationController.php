<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocumentVerificationController extends Controller
{
    /**
     * Verify document by UUID
     */
    public function verify($uuid)
    {
        $models = [
            'purchase_order' => \App\Models\PurchaseOrder::class,
            'goods_receipt' => \App\Models\GoodsReceipt::class,
            'return_transaction' => \App\Models\ReturnTransaction::class,
            'sale' => \App\Models\Sale::class,
            'stock_transfer' => \App\Models\StockTransfer::class,
        ];

        $document = null;
        $type = null;

        foreach ($models as $docType => $modelClass) {
            if ($docType === 'stock_transfer') {
                $doc = $modelClass::withoutGlobalScopes()
                    ->with(['sourceBranch', 'destinationBranch', 'createdBy', 'preparedBy', 'approvedBy', 'receivedBy', 'items.product'])
                    ->where('uuid', $uuid)
                    ->first();
            } else {
                $doc = $modelClass::withoutGlobalScopes()
                    ->where('uuid', $uuid)
                    ->first();
            }
            if ($doc) {
                $document = $doc;
                $type = $docType;
                break;
            }
        }

        if (!$document) {
            return response()->json(['valid' => false, 'message' => 'Dokumen tidak ditemukan atau QR Code tidak valid'], 404);
        }

        if ($type === 'stock_transfer') {
            return response()->json([
                'valid' => true,
                'type' => 'stock_transfer',
                'status' => $document->status,
                'reference_number' => $document->reference_no,
                'created_at' => $document->created_at,
                'notes' => $document->notes,
                'source_branch' => $document->sourceBranch ? $document->sourceBranch->name : '-',
                'destination_branch' => $document->destinationBranch ? $document->destinationBranch->name : '-',
                
                // 1. Pengirim / Penyiapan Asal
                'created_by' => $document->createdBy ? $document->createdBy->name : null,
                'prepared_by' => $document->preparedBy ? $document->preparedBy->name : null,
                'prepared_at' => $document->prepared_at,
                
                // 2. Kurir / Penjemput
                'picked_up_by_name' => $document->picked_up_by_name,
                'picked_up_at' => $document->picked_up_at,
                'pickup_courier_type' => $document->pickup_courier_type,
                'pickup_notes' => $document->pickup_notes,
                'pickup_photo' => $document->pickup_photo,
                
                // 3. Penerima Toko Tujuan
                'received_by' => $document->receivedBy ? $document->receivedBy->name : null,
                'received_at' => $document->received_at,
                'receive_notes' => $document->receive_notes,
                'received_photo' => $document->received_photo,

                // Items list
                'items' => $document->items->map(function ($it) {
                    return [
                        'sku' => $it->product ? $it->product->sku : '-',
                        'name' => $it->product ? $it->product->name : '-',
                        'qty_requested' => $it->qty,
                        'qty_prepared' => $it->qty_prepared ?? $it->qty,
                        'qty_picked' => $it->qty_picked ?? $it->qty_prepared ?? $it->qty,
                        'qty_received' => $it->qty_received,
                        'receive_condition' => $it->receive_condition ?? 'good',
                        'item_notes' => $it->item_notes,
                    ];
                }),
            ]);
        }

        // Determine if fully approved
        $isValid = $document->approval_status === 'approved' || ($document->approved_by != null);

        return response()->json([
            'valid' => true,
            'type' => $type,
            'status' => $document->approval_status,
            'validated_by' => $document->validator ? $document->validator->name : null,
            'validated_at' => $document->validated_at,
            'approved_by' => $document->approver ? $document->approver->name : null,
            'approved_at' => $document->approved_at,
            'created_at' => $document->created_at,
            'reference_number' => $this->getReferenceNumber($document, $type)
        ]);
    }

    /**
     * Submit a draft document for validation
     */
    public function submitDocument(Request $request, $type, $id)
    {
        $modelClass = $this->getModelClass($type);
        if (!$modelClass) return response()->json(['error' => 'Invalid type'], 400);

        $document = $modelClass::find($id);
        if (!$document) return response()->json(['error' => 'Not found'], 404);

        $document->approval_status = 'pending';
        $document->save();

        if ($type === 'purchase_order') {
            $admins = \App\Models\User::permission('Purchase Order Approve')->get();
            $title = 'Otorisasi Purchase Order Baru';
            $message = 'PO ' . $document->po_number . ' membutuhkan persetujuan/otorisasi Anda.';
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\DocumentNeedsApproval($title, $message, '/purchase-orders', 'warning', 'ri-file-warning-line'));
        } elseif ($type === 'return_transaction') {
            $admins = \App\Models\User::permission('approve Documents')->get();
            $title = 'Otorisasi Retur Baru';
            $message = 'Retur ' . $document->return_number . ' membutuhkan persetujuan/otorisasi Anda.';
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\DocumentNeedsApproval($title, $message, '/retur', 'warning', 'ri-file-warning-line'));
        } elseif ($type === 'goods_receipt') {
            $admins = \App\Models\User::permission('approve Documents')->get();
            $title = 'Otorisasi Penerimaan Gudang Baru';
            $message = 'Penerimaan Barang ' . $document->receipt_number . ' membutuhkan persetujuan/otorisasi Anda.';
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\DocumentNeedsApproval($title, $message, '/penerimaan-barang', 'warning', 'ri-file-warning-line'));
        }

        return response()->json(['message' => 'Document submitted for validation']);
    }

    /**
     * Validate a document
     */
    public function validateDocument(Request $request, $type, $id)
    {
        $modelClass = $this->getModelClass($type);
        if (!$modelClass) return response()->json(['error' => 'Invalid type'], 400);

        $document = $modelClass::find($id);
        if (!$document) return response()->json(['error' => 'Not found'], 404);

        $document->validated_by = $request->user()->id;
        $document->validated_at = now();
        $document->approval_status = 'validated';
        $document->save();

        return response()->json(['message' => 'Document validated successfully']);
    }

    /**
     * Approve a document
     */
    public function approveDocument(Request $request, $type, $id)
    {
        $modelClass = $this->getModelClass($type);
        if (!$modelClass) return response()->json(['error' => 'Invalid type'], 400);

        $document = $modelClass::find($id);
        if (!$document) return response()->json(['error' => 'Not found'], 404);

        // Can optionally also set validator if approving implies validation
        if (!$document->validated_by) {
            $document->validated_by = $request->user()->id;
            $document->validated_at = now();
        }

        $document->approved_by = $request->user()->id;
        $document->approved_at = now();
        $document->approval_status = 'approved';
        
        // Otomatis ubah status akhir dokumen menjadi completed (KECUALI Purchase Order, karena nunggu barang datang)
        if ($type !== 'purchase_order') {
            if (in_array('status', $document->getFillable()) || $document->status !== null) {
                $document->status = 'completed';
            }
        }

        if ($type === 'goods_receipt') {
            \Illuminate\Support\Facades\DB::transaction(function () use ($document, $request) {
                $document->load(['items.purchaseOrderItem', 'purchaseOrder.branch']);
                $po = $document->purchaseOrder;
                
                foreach ($document->items as $item) {
                    // HANYA item yang diceklis (is_received == true) dan qty_received > 0 yang menambah stok fisik & batch
                    if ($item->is_received && $item->qty_received > 0) {
                        $productId = $item->purchaseOrderItem ? $item->purchaseOrderItem->product_id : null;
                        if (!$productId && $item->product_branch_id) {
                            $pb = \App\Models\ProductBranch::find($item->product_branch_id);
                            $productId = $pb ? $pb->product_id : null;
                        }

                        if ($productId) {
                            // Find or create product_branch
                            $productBranch = \App\Models\ProductBranch::firstOrCreate(
                                ['branch_id' => $po->branch_id, 'product_id' => $productId],
                                ['stock' => 0, 'cost_price' => 0, 'price' => 0, 'tax_percentage' => 0]
                            );
                            
                            $item->update(['product_branch_id' => $productBranch->id]);
                            
                            $poItem = $item->purchaseOrderItem;
                            $actualCostPerPiece = $item->final_cost_per_piece > 0 
                                ? (float) $item->final_cost_per_piece 
                                : ($poItem ? (float) ($poItem->final_cost_per_piece > 0 ? $poItem->final_cost_per_piece : $poItem->unit_cost) : 0);
                            
                            $stockNotes = 'Penerimaan Barang dari PO: ' . $po->po_number;
                            if ($item->scc_code) {
                                $stockNotes .= ' [SCC: ' . $item->scc_code . ']';
                            }
                            if ($item->batch_number) {
                                $stockNotes .= ' [Batch: ' . $item->batch_number . ']';
                            }

                            // Create Stock Movement
                            \App\Models\StockMovement::create([
                                'product_branch_id' => $productBranch->id,
                                'user_id' => $request->user()->id,
                                'type' => 'in',
                                'quantity' => $item->qty_received,
                                'unit_cost' => $actualCostPerPiece,
                                'reference_type' => 'goods_receipt',
                                'reference_id' => $document->id,
                                'notes' => $stockNotes,
                            ]);
                            
                            // Update Stock & Cost Price
                            $productBranch->increment('stock', $item->qty_received);
                            if ($actualCostPerPiece > 0) {
                                $productBranch->update(['cost_price' => $actualCostPerPiece]);
                            }

                            // Create Product Batch for FIFO/LIFO/FEFO (menyimpan SCC dan Nomor Batch)
                            \App\Models\ProductBatch::create([
                                'product_branch_id' => $productBranch->id,
                                'batch_number' => $item->batch_number,
                                'scc_code' => $item->scc_code,
                                'qty' => $item->qty_received,
                                'cost_price' => $actualCostPerPiece,
                                'price' => $item->price ?? 0,
                                'min_nego_price' => $item->min_nego_price ?? 0,
                                'entry_date' => $document->date,
                                'expiration_date' => $item->expiration_date,
                            ]);
                        }
                    }
                }
                
                $po->update(['status' => 'completed']);
                
                // Kirim notifikasi ke pembuat dokumen Penerimaan Gudang
                $creator = \App\Models\User::find($document->user_id);
                if ($creator) {
                    $title = 'Penerimaan Gudang Disetujui!';
                    $message = 'Penerimaan barang dari PO ' . $po->po_number . ' telah disetujui. Stok di cabang ' . ($po->branch ? $po->branch->name : '') . ' resmi bertambah.';
                    $creator->notify(new \App\Notifications\StockUpdated($title, $message, '/inventori-cabang'));
                }
            });
        }

        $document->save();

        return response()->json(['message' => 'Document approved successfully']);
    }

    /**
     * Reject a document
     */
    public function rejectDocument(Request $request, $type, $id)
    {
        $modelClass = $this->getModelClass($type);
        if (!$modelClass) return response()->json(['error' => 'Invalid type'], 400);

        $document = $modelClass::find($id);
        if (!$document) return response()->json(['error' => 'Not found'], 404);

        $document->approval_status = 'rejected';
        
        // Otomatis ubah status akhir dokumen menjadi cancelled
        if (in_array('status', $document->getFillable()) || $document->status !== null) {
            $document->status = 'cancelled';
        }
        
        if ($request->has('reason')) {
            $document->rejection_reason = $request->reason;
        }
        
        $document->save();

        return response()->json(['message' => 'Document rejected successfully']);
    }

    private function getModelClass($type)
    {
        $models = [
            'purchase_order' => \App\Models\PurchaseOrder::class,
            'goods_receipt' => \App\Models\GoodsReceipt::class,
            'return_transaction' => \App\Models\ReturnTransaction::class,
            'sale' => \App\Models\Sale::class,
        ];
        return $models[$type] ?? null;
    }

    private function getReferenceNumber($document, $type)
    {
        if ($type === 'purchase_order') return $document->po_number;
        if ($type === 'goods_receipt') return $document->receipt_number;
        if ($type === 'return_transaction') return $document->return_number;
        if ($type === 'sale') return $document->invoice_number ?? $document->id;
        
        return $document->id;
    }
}
