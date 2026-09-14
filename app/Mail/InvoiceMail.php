<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $pdfPath;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $pdfPath)
    {
        $this->data = $data;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = 'Tagihan (Invoice) - ' . ($this->data['reference_no'] ?? 'Dokumen');
        
        $mail = $this->subject($subject)
                    ->view('emails.invoice')
                    ->with('data', $this->data);

        if ($this->pdfPath && file_exists($this->pdfPath)) {
            $mail->attach($this->pdfPath, [
                'as' => 'Invoice_' . ($this->data['reference_no'] ?? 'Doc') . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
