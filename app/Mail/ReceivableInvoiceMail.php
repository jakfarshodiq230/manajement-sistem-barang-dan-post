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

        return $this->subject("Surat Tagihan Faktur Piutang #{$invNo} - {$custName}")
                    ->view('emails.receivables.invoice');
    }
}
