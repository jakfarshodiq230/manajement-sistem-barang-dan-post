<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity;

    protected $fillable = [
        'sale_id',
        'product_branch_id',
        'qty',
        'original_price',
        'price',
        'cost_price',
        'tax_percentage',
        'tax_amount',
        'subtotal',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function productBranch()
    {
        return $this->belongsTo(ProductBranch::class);
    }
}
