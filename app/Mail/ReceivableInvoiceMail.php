<?php

namespace App\Mail;

use App\Models\Receivable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReceivableInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $receivable;

    public function __construct(Receivable $receivable)
    {
        $this->receivable = $receivable;
    }

    public function build()
    {
        $invNo = $this->receivable->sale->invoice_number ?? ('REC-' . str_pad($this->receivable->id, 5, '0', STR_PAD_LEFT));
        $custName = $this->receivable->customer->name ?? 'Pelanggan';

        $mail = $this->subject("Surat Tagihan Faktur Piutang #{$invNo} - {$custName}")
                    ->view('emails.invoice')
                    ->with(['data' => [
                        'customer_name' => $custName,
                        'reference_no' => $invNo,
                        'amount' => $this->receivable->amount_due
                    ]]);

        // Generate PDF
        if ($this->receivable->sale) {
            $sale = $this->receivable->sale;
            $sale->loadMissing(['branch.owner', 'items.productBranch.product', 'user.employee', 'validator.employee', 'approver.employee']);
            
            $branch = $sale->branch ?? \App\Models\Branch::with('owner')->first();
            
            $docType = 'Sale';
            $docNumber = $sale->invoice_number;
            $docDate = $sale->date ?? $sale->created_at;
            $docTotal = $sale->total_amount;
            $branchName = $branch->name ?? 'Cabang Utama';
            
            $docPayload = "VERIFIKASI KEABSAHAN DOKUMEN MS.POS\n"
                . "Dokumen   : " . $docType . "\n"
                . "Nomor     : " . $docNumber . "\n"
                . "Cabang    : " . $branchName . "\n"
                . "Tanggal   : " . date('d/m/Y', strtotime($docDate)) . "\n"
                . "Nilai/Total: Rp " . number_format($docTotal, 0, ',', '.');
            $qrCode = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(75)->generate($docPayload));

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.sale', [
                'document' => $sale,
                'branch' => $branch,
                'qrCode' => $qrCode,
                'userQrCode' => null,
                'validatorQrCode' => null,
                'approverQrCode' => null,
                'type' => 'sale'
            ]);

            $mail->attachData($pdf->output(), "Invoice_{$invNo}.pdf", [
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
