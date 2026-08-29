<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptItem extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity;

    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'product_branch_id',
        'unit_name',
        'conversion_qty',
        'qty_received',
        'gross_price',
        'discount_percent_1',
        'discount_percent_2',
        'discount_percent_3',
        'discount_percent_4',
        'discount_percent_5',
        'discount_string',
        'discount_amount',
        'net_unit_price',
        'price',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'min_nego_price',
        'final_cost_per_piece',
        'expiration_date',
        'batch_number',
        'scc_code',
        'is_received',
        'qty_rejected',
        'rejection_reason',
    ];

    protected $casts = [
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'gross_price' => 'decimal:2',
        'net_unit_price' => 'decimal:2',
        'price' => 'decimal:2',
        'min_nego_price' => 'decimal:2',
        'final_cost_per_piece' => 'decimal:2',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }

    public function paymentAllocations()
    {
        return $this->hasMany(PayablePaymentItem::class, 'goods_receipt_item_id');
    }

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function productBranch()
    {
        return $this->belongsTo(ProductBranch::class);
    }

    public function product()
    {
        return $this->hasOneThrough(Product::class, ProductBranch::class, 'id', 'id', 'product_branch_id', 'product_id');
    }
}
