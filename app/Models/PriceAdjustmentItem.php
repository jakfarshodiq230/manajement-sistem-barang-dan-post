<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceAdjustmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_adjustment_id',
        'product_id',
        'product_branch_id',
        'old_cost_price',
        'new_cost_price',
        'old_price',
        'new_price',
        'old_min_nego_price',
        'new_min_nego_price',
        'notes',
    ];

    protected $casts = [
        'old_cost_price' => 'decimal:2',
        'new_cost_price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'old_min_nego_price' => 'decimal:2',
        'new_min_nego_price' => 'decimal:2',
    ];

    public function priceAdjustment()
    {
        return $this->belongsTo(PriceAdjustment::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productBranch()
    {
        return $this->belongsTo(ProductBranch::class);
    }
}
