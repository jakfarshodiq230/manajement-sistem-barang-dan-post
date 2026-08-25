<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CapitalSummaryReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $summary;

    public function __construct(array $summary)
    {
        $this->summary = $summary;
    }

    public function build()
    {
        $date = date('d/m/Y');
        $roi = $this->summary['payback_percentage'] ?? 0;

        return $this->subject("Laporan Eksekutif Portofolio Modal & ROI ({$roi}%) - {$date}")
                    ->view('emails.capitals.summary');
    }
}
