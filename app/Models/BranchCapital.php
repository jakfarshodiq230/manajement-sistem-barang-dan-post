<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\ScopedByBranch;

class BranchCapital extends Model
{
    use HasFactory, LogsActivity, ScopedByBranch;

    protected $fillable = [
        'reference_no',
        'branch_id',
        'cash_shift_id',
        'user_id',
        'type',
        'category',
        'amount',
        'date',
        'payment_method',
        'bank_name',
        'account_number',
        'account_name',
        'proof_file',
        'notes',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cashShift()
    {
        return $this->belongsTo(CashShift::class);
    }
}
