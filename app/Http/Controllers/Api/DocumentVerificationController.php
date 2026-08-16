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
        ];

        $document = null;
        $type = null;

        foreach ($models as $docType => $modelClass) {
            $doc = $modelClass::with(['validator', 'approver'])->where('uuid', $uuid)->first();
            if ($doc) {
                $document = $doc;
                $type = $docType;
                break;
            }
        }

        if (!$document) {
            return response()->json(['valid' => false, 'message' => 'Document not found'], 404);
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
                $document->load('items', 'purchaseOrder');
                $po = $document->purchaseOrder;
                
                foreach ($document->items as $item) {
                    if ($item->qty_received > 0) {
                        // Find or create product_branch
                        $productBranch = \App\Models\ProductBranch::firstOrCreate(
                            ['branch_id' => $po->branch_id, 'product_id' => $item->purchaseOrderItem->product_id],
                            ['stock' => 0, 'cost_price' => 0, 'price' => 0, 'tax_percentage' => 0]
                        );
                        
                        $item->update(['product_branch_id' => $productBranch->id]);
                        
                        $poItem = \App\Models\PurchaseOrderItem::find($item->purchase_order_item_id);
                        
                        // Create Stock Movement
                        \App\Models\StockMovement::create([
                            'product_branch_id' => $productBranch->id,
                            'user_id' => $request->user()->id,
                            'type' => 'in',
                            'quantity' => $item->qty_received,
                            'unit_cost' => $poItem ? $poItem->unit_cost : 0,
                            'reference_type' => 'goods_receipt',
                            'reference_id' => $document->id,
                            'notes' => 'Penerimaan Barang dari PO: ' . $po->po_number,
                        ]);
                        
                        // Update Stock
                        $productBranch->increment('stock', $item->qty_received);
                        if ($poItem) {
                            $productBranch->update(['cost_price' => $poItem->unit_cost]);
                        }

                        // Create Product Batch for FIFO/LIFO/FEFO
                        \App\Models\ProductBatch::create([
                            'product_branch_id' => $productBranch->id,
                            'qty' => $item->qty_received,
                            'cost_price' => $poItem ? $poItem->unit_cost : 0,
                            'price' => $item->price ?? 0,
                            'min_nego_price' => $item->min_nego_price ?? 0,
                            'entry_date' => $document->date,
                            'expiration_date' => $item->expiration_date,
                        ]);
                    }
                }
                
                $po->update(['status' => 'completed']);
                
                // Kirim notifikasi ke pembuat dokumen Penerimaan Gudang
                $creator = \App\Models\User::find($document->user_id);
                if ($creator) {
                    $title = 'Penerimaan Gudang Disetujui!';
                    $message = 'Penerimaan barang dari PO ' . $po->po_number . ' telah disetujui. Stok di cabang ' . $po->branch->name . ' resmi bertambah.';
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
