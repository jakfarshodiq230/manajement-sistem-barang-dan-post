<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $entry_number
 * @property \Carbon\Carbon $entry_date
 * @property int|null $branch_id
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $notes
 * @property string $status
 * @property int|null $created_by
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\JournalEntryItem[] $items
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\User|null $user
 * @property-read float $total_debit
 * @property-read float $total_credit
 * @property-read bool $is_balanced
 */
class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_number',
        'entry_date',
        'branch_id',
        'reference_type',
        'reference_id',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(JournalEntryItem::class, 'journal_entry_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalDebitAttribute()
    {
        return $this->items->sum('debit');
    }

    public function getTotalCreditAttribute()
    {
        return $this->items->sum('credit');
    }

    public function getIsBalancedAttribute()
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }
}
