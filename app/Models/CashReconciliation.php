<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashReconciliation extends Model
{
    use HasFactory, \App\Traits\ScopedByBranch;

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'variance' => 'decimal:2',
        'cash_sales_amount' => 'decimal:2',
        'dp_cash_amount' => 'decimal:2',
        'receivable_payments_amount' => 'decimal:2',
        'capital_returns_amount' => 'decimal:2',
        'capital_injections_amount' => 'decimal:2',
        'petty_cash_amount' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
