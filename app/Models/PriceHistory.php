<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_branch_id',
        'branch_id',
        'price_adjustment_id',
        'adjustment_number',
        'old_cost_price',
        'new_cost_price',
        'old_price',
        'new_price',
        'old_min_nego_price',
        'new_min_nego_price',
        'effective_date',
        'reason',
        'user_id',
    ];

    protected $casts = [
        'old_cost_price' => 'decimal:2',
        'new_cost_price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'old_min_nego_price' => 'decimal:2',
        'new_min_nego_price' => 'decimal:2',
        'effective_date' => 'date:Y-m-d',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productBranch()
    {
        return $this->belongsTo(ProductBranch::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function priceAdjustment()
    {
        return $this->belongsTo(PriceAdjustment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
