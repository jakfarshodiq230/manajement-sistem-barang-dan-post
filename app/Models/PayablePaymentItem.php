<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayablePaymentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payable_payment_id',
        'goods_receipt_item_id',
        'payable_id',
        'allocated_amount',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function payment()
    {
        return $this->belongsTo(PayablePayment::class, 'payable_payment_id');
    }

    public function goodsReceiptItem()
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }

    public function payable()
    {
        return $this->belongsTo(Payable::class);
    }
}
