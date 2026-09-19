<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Receivable;
use App\Services\EmailNotificationService;
use App\Services\NotificationService;
use Carbon\Carbon;

class SendReceivableDueReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'receivables:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim email pengingat tagihan piutang jatuh tempo ke pelanggan secara otomatis';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Memulai pengecekan piutang jatuh tempo...");

        $today = Carbon::today();

        // Find unpaid/partial receivables with due date
        $receivables = Receivable::with(['customer', 'sale.branch'])
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereNotNull('due_date')
            ->get();

        $receivablesToRemind = $receivables->filter(function($receivable) use ($today) {
            $dueDate = Carbon::parse($receivable->due_date)->startOfDay();
            $diffInDays = $today->diffInDays($dueDate, false); // false = negative if today > dueDate
            
            // Hari H (0), H-2 (2), H-7 (7)
            return in_array($diffInDays, [0, 2, 7], true);
        });

        $sentCount = 0;
        $failedCount = 0;

        foreach ($receivablesToRemind as $receivable) {
            $customer = $receivable->customer;
            if (!$customer || !$customer->email) {
                continue;
            }

            try {
                EmailNotificationService::sendReceivableInvoice($receivable, $customer->email, 'automatic');
                $sentCount++;
                $this->line(" - [Terkirim] Tagihan #{$receivable->id} ke {$customer->name} ({$customer->email})");
            } catch (\Exception $e) {
                $failedCount++;
                $this->error(" - [Gagal] Tagihan #{$receivable->id}: " . $e->getMessage());
            }
        }

        $this->info("Pengecekan selesai. {$sentCount} email terkirim, {$failedCount} gagal.");

        return 0;
    }
}
