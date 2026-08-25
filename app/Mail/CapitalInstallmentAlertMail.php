<?php

namespace App\Mail;

use App\Models\BranchCapital;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CapitalInstallmentAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $capital;

    public function __construct(BranchCapital $capital)
    {
        $this->capital = $capital;
    }

    public function build()
    {
        $branchName = $this->capital->branch->name ?? 'Cabang';
        $formattedAmount = 'Rp ' . number_format($this->capital->amount, 0, ',', '.');

        return $this->subject("Setoran Pengembalian Modal Masuk ({$formattedAmount}) - {$branchName}")
                    ->view('emails.capitals.installment');
    }
}
