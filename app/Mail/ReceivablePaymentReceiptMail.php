<?php

namespace App\Mail;

use App\Models\ReceivablePayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReceivablePaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public $payment;

    public function __construct(ReceivablePayment $payment)
    {
        $this->payment = $payment;
    }

    public function build()
    {
        $invNo = $this->payment->receivable->sale->invoice_number ?? ('REC-' . str_pad($this->payment->receivable->id, 5, '0', STR_PAD_LEFT));
        $custName = $this->payment->receivable->customer->name ?? 'Pelanggan';

        $mail = $this->subject("Kuitansi Pembayaran Cicilan #{$invNo} - {$custName}")
                    ->view('emails.receipt')
                    ->with(['data' => [
                        'customer_name' => $custName,
                        'reference_no' => $invNo,
                        'amount' => $this->payment->amount
                    ]]);
        
        // Generate PDF
        if ($this->payment->receivable && $this->payment->receivable->sale) {
            $sale = $this->payment->receivable->sale;
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

            $mail->attachData($pdf->output(), "Kuitansi_{$invNo}.pdf", [
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
