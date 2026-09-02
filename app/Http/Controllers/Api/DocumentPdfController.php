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

        $viewName = $this->getViewName($type);

        // Load specific relations depending on type
        if ($type === 'purchase_order') {
            $document->load(['supplier', 'items.product', 'branch.owner', 'user.employee', 'validator.employee', 'approver.employee']);
        } elseif ($type === 'goods_receipt') {
            $document->load(['purchaseOrder.supplier', 'purchaseOrder.branch.owner', 'items.productBranch.product', 'user.employee', 'validator.employee', 'approver.employee']);
        } elseif ($type === 'return_transaction') {
            $document->load(['branch.owner', 'items.product', 'user.employee', 'validator.employee', 'approver.employee']);
        } elseif ($type === 'sale') {
            $document->load(['branch.owner', 'items.product', 'user.employee', 'validator.employee', 'approver.employee']);
        } elseif ($type === 'payable_statement') {
            $document->load([
                'supplier',
                'branch.owner',
                'payables.goodsReceipt.items.productBranch.product',
                'payables.purchaseOrder',
                'payments.bankAccount',
                'payments.user.employee',
                'creator.employee',
            ]);
        }

        $branch = null;
        if (isset($document->branch) && $document->branch) {
            $branch = $document->branch;
        } elseif (isset($document->purchaseOrder) && isset($document->purchaseOrder->branch) && $document->purchaseOrder->branch) {
            $branch = $document->purchaseOrder->branch;
        }

        if (!$branch) {
            $branch = \App\Models\Branch::with('owner')->first();
        }

        if ($branch && !$branch->relationLoaded('owner')) {
            $branch->load('owner');
        }

        $branchName = $branch->name ?? 'Cabang Utama';
        $docType = ucwords(str_replace('_', ' ', $type));
        $docNumber = $document->receipt_number ?? ($document->po_number ?? ($document->statement_number ?? ($document->invoice_number ?? ($document->return_number ?? ('DOC-' . $document->id)))));
        $docDate = $document->date ?? ($document->created_at ?? date('Y-m-d'));
        $docTotal = $document->total_amount ?? 0;

        // 1. QR Code Bukti Keabsahan Dokumen (Terbaca saat di-scan)
        $docPayload = "VERIFIKASI KEABSAHAN DOKUMEN MS.POS\n"
            . "====================================\n"
            . "Dokumen   : " . $docType . "\n"
            . "Nomor     : " . $docNumber . "\n"
            . "Cabang    : " . $branchName . "\n"
            . "Tanggal   : " . date('d/m/Y', strtotime($docDate)) . "\n"
            . "Nilai/Total: Rp " . number_format($docTotal, 0, ',', '.') . "\n"
            . "Status    : DOKUMEN SAH & TERCATAT RESMI DI SISTEM\n"
            . "Tgl Cetak : " . date('d/m/Y H:i:s');
        $qrCode = base64_encode(QrCode::format('svg')->size(75)->generate($docPayload));

        // 2. QR Code Tanda Tangan Digital Petugas / Kasir (Identitas Penandatangan)
        $userQrCode = null;
        $userObj = $document->user ?? $document->creator ?? null;
        if ($userObj) {
            $userNip = $userObj->nip ?? ($userObj->employee->nik ?? 'EMP-' . str_pad($userObj->id, 3, '0', STR_PAD_LEFT));
            $userRole = ($userObj->role) ? $userObj->role->name : 'Petugas Operasional';
            $signerPayload = "TANDA TANGAN DIGITAL RESMI (DIGITAL SIGNATURE)\n"
                . "===============================================\n"
                . "Penandatangan : " . $userObj->name . "\n"
                . "NIP / ID      : " . $userNip . "\n"
                . "Jabatan       : " . $userRole . "\n"
                . "Unit / Cabang : " . $branchName . "\n"
                . "Waktu TTD     : " . date('d/m/Y H:i:s', strtotime($document->created_at ?: 'now')) . "\n"
                . "Dokumen       : " . $docType . " (" . $docNumber . ")\n"
                . "Status TTD    : TERTANDA DIGITAL SAH (VERIFIED)";
            $userQrCode = base64_encode(QrCode::format('svg')->size(75)->generate($signerPayload));
        }

        // 3. QR Code Tanda Tangan Digital Validator
        $validatorQrCode = null;
        if ($document->validator) {
            $vObj = $document->validator;
            $vNip = $vObj->nip ?? ($vObj->employee->nik ?? 'EMP-' . str_pad($vObj->id, 3, '0', STR_PAD_LEFT));
            $vRole = ($vObj->role) ? $vObj->role->name : 'Supervisor / Pemeriksa';
            $vPayload = "TANDA TANGAN DIGITAL RESMI (VALIDATOR SIGNATURE)\n"
                . "=================================================\n"
                . "Validator     : " . $vObj->name . "\n"
                . "NIP / ID      : " . $vNip . "\n"
                . "Jabatan       : " . $vRole . "\n"
                . "Unit / Cabang : " . $branchName . "\n"
                . "Waktu Validasi: " . date('d/m/Y H:i:s') . "\n"
                . "Dokumen       : " . $docType . " (" . $docNumber . ")\n"
                . "Status        : TERVALIDASI SAH (VALIDATED)";
            $validatorQrCode = base64_encode(QrCode::format('svg')->size(75)->generate($vPayload));
        }

        $approverQrCode = null;

        $data = [
            'document'        => $document,
            'branch'          => $branch,
            'qrCode'          => $qrCode,
            'userQrCode'      => $userQrCode,
            'validatorQrCode' => $validatorQrCode,
            'approverQrCode'  => null,
            'type'            => $type
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
            'payable_statement' => \App\Models\PayableStatement::class,
        ];
        return $models[$type] ?? null;
    }

    private function getViewName($type)
    {
        return 'pdf.' . $type;
    }
}
