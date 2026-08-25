<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'unit_name',
        'conversion_qty',
        'qty',
        'gross_price',
        'discount_percent_1',
        'discount_percent_2',
        'discount_percent_3',
        'discount_percent_4',
        'discount_percent_5',
        'discount_string',
        'discount_amount',
        'net_unit_price',
        'unit_cost',
        'total_price',
        'final_cost_per_piece',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
