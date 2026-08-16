<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class DocumentPdfController extends Controller
{
    /**
     * Download PDF for a document type and ID
     */
    public function download(Request $request, $type, $id)
    {
        $modelClass = $this->getModelClass($type);
        if (!$modelClass) {
            return response()->json(['error' => 'Invalid document type'], 400);
        }

        $document = $modelClass::with(['validator.employee', 'approver.employee'])->find($id);
        if (!$document) {
            return response()->json(['error' => 'Document not found'], 404);
        }

        // Generate UUID if it doesn't exist
        if (!$document->uuid) {
            $document->uuid = (string) Str::uuid();
            $document->save();
        }

        // Generate QR Code (SVG format to pass to PDF)
        $verifyUrl = url('/verify/' . $document->uuid);
        $qrCode = base64_encode(QrCode::format('svg')->size(100)->generate($verifyUrl));

        $viewName = $this->getViewName($type);
        
        // Load specific relations depending on type
        if ($type === 'purchase_order') {
            $document->load(['supplier', 'items.product', 'branch.owner', 'user.employee']);
        } elseif ($type === 'goods_receipt') {
            $document->load(['purchaseOrder.supplier', 'purchaseOrder.branch.owner', 'items.productBranch.product', 'user.employee']);
        } elseif ($type === 'return_transaction') {
            $document->load(['branch.owner', 'items.product', 'user.employee']);
        } elseif ($type === 'sale') {
            $document->load(['branch.owner', 'items.product', 'user.employee']);
        }

        $branch = null;
        if (isset($document->branch)) {
            $branch = $document->branch;
        } elseif (isset($document->purchaseOrder) && isset($document->purchaseOrder->branch)) {
            $branch = $document->purchaseOrder->branch;
        }

        $data = [
            'document' => $document,
            'branch' => $branch,
            'qrCode' => $qrCode,
            'verifyUrl' => $verifyUrl,
            'type' => $type
        ];

        $pdf = Pdf::loadView($viewName, $data);
        return $pdf->download($type . '_' . $id . '.pdf');
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

    private function getViewName($type)
    {
        return 'pdf.' . $type;
    }
}
