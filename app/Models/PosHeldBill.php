<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Scopes\BranchScope;

class PosHeldBill extends Model
{
    use HasFactory;

    protected $table = 'pos_held_bills';

    protected $fillable = [
        'branch_id',
        'user_id',
        'customer_id',
        'customer_name',
        'subtotal',
        'discount',
        'total',
        'items_json',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'items_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
