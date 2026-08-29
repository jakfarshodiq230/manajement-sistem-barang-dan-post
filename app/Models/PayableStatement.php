<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayableStatement extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity, \App\Traits\ScopedByBranch;

    protected $fillable = [
        'statement_number',
        'supplier_id',
        'branch_id',
        'period_month',
        'period_year',
        'cutoff_day',
        'period_start_date',
        'period_end_date',
        'due_date',
        'total_invoices_count',
        'total_purchases_amount',
        'total_returns_deduction',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'due_date' => 'date',
        'total_purchases_amount' => 'decimal:2',
        'total_returns_deduction' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payables()
    {
        return $this->hasMany(Payable::class)->orderBy('invoice_date', 'asc');
    }

    public function payments()
    {
        return $this->hasMany(PayablePayment::class)->orderBy('payment_date', 'desc');
    }

    /**
     * Recalculate statement totals and automatically allocate payments to all individual invoices in this billing cycle.
     */
    public function recalculateTotals()
    {
        $this->load([
            'payables' => function ($q) {
                $q->orderBy('invoice_date', 'asc')->orderBy('id', 'asc');
            },
            'payments',
        ]);

        $totalPurchases = (float) $this->payables->sum('total_amount');
        $invoicesCount = $this->payables->count();

        // Net amount is purchases minus returns
        $netTotal = max(0, $totalPurchases - (float) $this->total_returns_deduction);
        $totalPaid = (float) $this->payments->sum('amount');
        $remaining = max(0, $netTotal - $totalPaid);

        $status = 'unpaid';
        if ($remaining <= 0 && $netTotal > 0) {
            $status = 'paid';
        } elseif ($totalPaid > 0) {
            $status = 'partial';
        }

        $this->update([
            'total_invoices_count' => $invoicesCount,
            'total_purchases_amount' => $totalPurchases,
            'total_amount' => $netTotal,
            'paid_amount' => $totalPaid,
            'remaining_amount' => $remaining,
            'status' => $status,
        ]);

        // Otomatis sinkronisasi & lunasi seluruh faktur dan barang yang masuk dalam periode tagihan ini
        $availableFunds = $totalPaid;
        foreach ($this->payables as $payable) {
            $invAmount = (float) $payable->total_amount;
            if ($availableFunds >= ($invAmount - 0.01)) {
                $payable->paid_amount = $invAmount;
                $payable->remaining_amount = 0;
                $payable->status = 'paid';
                $availableFunds = max(0, $availableFunds - $invAmount);
            } elseif ($availableFunds > 0) {
                $payable->paid_amount = $availableFunds;
                $payable->remaining_amount = max(0, $invAmount - $availableFunds);
                $payable->status = 'partial';
                $availableFunds = 0;
            } else {
                $payable->paid_amount = 0;
                $payable->remaining_amount = $invAmount;
                $payable->status = 'unpaid';
            }
            $payable->save();
        }
    }
}
