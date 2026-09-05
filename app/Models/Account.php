<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'category',
        'normal_balance',
        'parent_id',
        'branch_id',
        'opening_balance',
        'is_system',
        'is_active',
        'description',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id')->orderBy('code');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function journalItems()
    {
        return $this->hasMany(JournalEntryItem::class, 'account_id');
    }

    /**
     * Calculate current balance for this account
     */
    public function getBalanceAttribute()
    {
        $debitTotal = $this->journalItems()->sum('debit') ?: 0;
        $creditTotal = $this->journalItems()->sum('credit') ?: 0;
        $opening = $this->opening_balance ?: 0;

        if ($this->normal_balance === 'debit') {
            return $opening + ($debitTotal - $creditTotal);
        } else {
            return $opening + ($creditTotal - $debitTotal);
        }
    }
}
