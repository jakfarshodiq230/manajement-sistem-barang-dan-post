<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'adjustment_number',
        'branch_id',
        'title',
        'effective_date',
        'reason',
        'status',
        'total_items',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date:Y-m-d',
        'approved_at' => 'datetime',
        'total_items' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(PriceAdjustmentItem::class);
    }

    public static function generateAdjustmentNumber(): string
    {
        $prefix = 'ADJ-PRC-' . date('Ym') . '-';
        $last = self::where('adjustment_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $lastNum = (int) substr($last->adjustment_number, strlen($prefix));
            $nextNum = str_pad((string)($lastNum + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $nextNum = '0001';
        }

        return $prefix . $nextNum;
    }
}
