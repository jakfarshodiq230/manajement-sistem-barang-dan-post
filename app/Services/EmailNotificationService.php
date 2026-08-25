<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\BranchCapital;
use App\Models\User;
use App\Mail\ReceivableInvoiceMail;
use App\Mail\ReceivablePaymentReceiptMail;
use App\Mail\CapitalInstallmentAlertMail;
use App\Mail\CapitalSummaryReportMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailNotificationService
{
    /**
     * Helper terpusat untuk mengirim Mailable dengan automatic audit logging di email_logs.
     */
    public static function dispatchWithLog(
        $mailable,
        string $recipientEmail,
        ?string $recipientName,
        string $emailType,
        string $referenceType,
        string $referenceId,
        string $triggerMode = 'manual',
        ?int $userId = null,
        ?int $branchId = null,
        ?array $metadata = null
    ): EmailLog {
        $subject = method_exists($mailable, 'build') ? ($mailable->build()->subject ?? 'Notifikasi Sistem') : 'Notifikasi Sistem';

        // 1. Create Log Entry with status 'pending'
        $log = EmailLog::create([
            'recipient_email' => $recipientEmail,
            'recipient_name'  => $recipientName,
            'subject'         => $subject,
            'email_type'      => $emailType,
            'trigger_mode'    => $triggerMode,
            'reference_type'  => $referenceType,
            'reference_id'    => (string) $referenceId,
            'status'          => 'pending',
            'user_id'         => $userId,
            'branch_id'       => $branchId,
            'metadata'        => $metadata,
        ]);

        // 2. Attempt Mail Dispatch with Safe Failover
        try {
            Mail::to($recipientEmail)->send($mailable);

            $log->update([
                'status'  => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error("Email dispatch failed for {$recipientEmail} [{$emailType}]: " . $e->getMessage());

            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    /**
     * Kirim Surat Tagihan Piutang (Invoice Statement).
     */
    public static function sendReceivableInvoice(Receivable $receivable, ?string $recipientEmail = null, string $triggerMode = 'manual', ?int $userId = null): EmailLog
    {
        $receivable->loadMissing(['customer', 'sale.items.productBranch.product', 'sale.branch']);
        $email = $recipientEmail ?: ($receivable->customer->email ?? null);
        $name = $receivable->customer->name ?? 'Pelanggan';

        if (!$email) {
            throw new \Exception("Pelanggan {$name} belum memiliki alamat email yang tersimpan.");
        }

        $mailable = new ReceivableInvoiceMail($receivable);
        $branchId = $receivable->sale->branch_id ?? null;

        return self::dispatchWithLog(
            $mailable,
            $email,
            $name,
            'receivable_invoice',
            Receivable::class,
            (string) $receivable->id,
            $triggerMode,
            $userId,
            $branchId,
            ['amount_due' => $receivable->amount_due, 'amount_paid' => $receivable->amount_paid]
        );
    }

    /**
     * Kirim Kwitansi Tanda Terima Pembayaran Cicilan Piutang.
     */
    public static function sendReceivableReceipt(ReceivablePayment $payment, ?string $recipientEmail = null, string $triggerMode = 'automatic', ?int $userId = null): EmailLog
    {
        $payment->loadMissing(['receivable.customer', 'receivable.sale', 'user']);
        $email = $recipientEmail ?: ($payment->receivable->customer->email ?? null);
        $name = $payment->receivable->customer->name ?? 'Pelanggan';

        if (!$email) {
            // If customer has no email, fallback log as failed or skip
            return EmailLog::create([
                'recipient_email' => 'no-email@customer.local',
                'recipient_name'  => $name,
                'subject'         => 'Kwitansi Pembayaran Piutang #' . $payment->id,
                'email_type'      => 'receivable_receipt',
                'trigger_mode'    => $triggerMode,
                'reference_type'  => ReceivablePayment::class,
                'reference_id'    => (string) $payment->id,
                'status'          => 'failed',
                'error_message'   => 'Pelanggan tidak memiliki alamat email yang tersimpan.',
                'user_id'         => $userId,
                'branch_id'       => $payment->receivable->sale->branch_id ?? null,
            ]);
        }

        $mailable = new ReceivablePaymentReceiptMail($payment);
        $branchId = $payment->receivable->sale->branch_id ?? null;

        return self::dispatchWithLog(
            $mailable,
            $email,
            $name,
            'receivable_receipt',
            ReceivablePayment::class,
            (string) $payment->id,
            $triggerMode,
            $userId,
            $branchId,
            ['payment_amount' => $payment->amount]
        );
    }

    /**
     * Kirim Notifikasi Setoran Angsuran / Pengembalian Modal ke Owner.
     */
    public static function sendCapitalInstallmentAlert(BranchCapital $capital, ?string $recipientEmail = null, string $triggerMode = 'automatic', ?int $userId = null): EmailLog
    {
        $capital->loadMissing(['branch', 'user']);
        
        // Find Owner / Admin email via database RBAC permissions or fallback
        $email = $recipientEmail;
        if (!$email) {
            $ownerUser = User::all()->first(function($u) {
                return $u->email && ($u->can('manage all') || $u->can('Modal & ROI Cabang Approve') || $u->can('Dashboard Keuntungan Read'));
            });

            $email = $ownerUser ? $ownerUser->email : env('MAIL_FROM_ADDRESS', 'owner@pt-dumai.com');
        }

        $mailable = new CapitalInstallmentAlertMail($capital);

        return self::dispatchWithLog(
            $mailable,
            $email,
            'Owner / Direksi PT. DUMAI',
            'capital_installment',
            BranchCapital::class,
            (string) $capital->id,
            $triggerMode,
            $userId,
            $capital->branch_id,
            ['amount' => $capital->amount, 'type' => $capital->type]
        );
    }

    /**
     * Kirim Ringkasan Portofolio Modal & ROI ke Owner.
     */
    public static function sendCapitalSummaryReport(array $summary, ?string $recipientEmail = null, string $triggerMode = 'manual', ?int $userId = null): EmailLog
    {
        $email = $recipientEmail;
        if (!$email) {
            $ownerUser = User::all()->first(function($u) {
                return $u->email && ($u->can('manage all') || $u->can('Modal & ROI Cabang Approve') || $u->can('Dashboard Keuntungan Read'));
            });

            $email = $ownerUser ? $ownerUser->email : env('MAIL_FROM_ADDRESS', 'owner@pt-dumai.com');
        }

        $mailable = new CapitalSummaryReportMail($summary);

        return self::dispatchWithLog(
            $mailable,
            $email,
            'Owner / Direksi PT. DUMAI',
            'capital_summary',
            'App\Models\CapitalSummary',
            'summary-' . date('Y-m-d'),
            $triggerMode,
            $userId,
            null,
            $summary
        );
    }

    /**
     * Retry pengiriman email yang berstatus failed.
     */
    public static function retry(int $logId): EmailLog
    {
        $log = EmailLog::findOrFail($logId);

        if ($log->email_type === 'receivable_invoice') {
            $receivable = Receivable::find($log->reference_id);
            if (!$receivable) throw new \Exception("Data piutang tidak ditemukan.");
            $mailable = new ReceivableInvoiceMail($receivable);
        } elseif ($log->email_type === 'receivable_receipt') {
            $payment = ReceivablePayment::find($log->reference_id);
            if (!$payment) throw new \Exception("Data kwitansi pembayaran tidak ditemukan.");
            $mailable = new ReceivablePaymentReceiptMail($payment);
        } elseif ($log->email_type === 'capital_installment') {
            $capital = BranchCapital::find($log->reference_id);
            if (!$capital) throw new \Exception("Data setoran modal tidak ditemukan.");
            $mailable = new CapitalInstallmentAlertMail($capital);
        } else {
            throw new \Exception("Tipe email tidak mendukung retry otomatis.");
        }

        $log->update(['status' => 'pending']);

        try {
            Mail::to($log->recipient_email)->send($mailable);

            $log->update([
                'status'        => 'sent',
                'sent_at'       => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $log;
    }
}
