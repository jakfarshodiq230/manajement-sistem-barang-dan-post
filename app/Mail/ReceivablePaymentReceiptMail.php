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
        $payId = 'PAY-' . str_pad($this->payment->id, 6, '0', STR_PAD_LEFT);
        $custName = $this->payment->receivable->customer->name ?? 'Pelanggan';

        return $this->subject("Kwitansi Bukti Pembayaran Piutang #{$payId} - {$custName}")
                    ->view('emails.receivables.receipt');
    }
}
