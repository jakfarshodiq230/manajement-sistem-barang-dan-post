<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Scopes\BranchScope;

class PettyCash extends Model
{
    use HasFactory;

    protected $table = 'petty_cashes';

    protected $fillable = [
        'branch_id',
        'user_id',
        'cash_shift_id',
        'category',
        'amount',
        'description',
        'receipt_image',
        'date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date:Y-m-d',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashShift()
    {
        return $this->belongsTo(CashShift::class);
    }
}
