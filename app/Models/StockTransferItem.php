<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StockTransferItem extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity;

    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'qty',
        'qty_prepared',
        'qty_picked',
        'qty_received',
        'receive_condition',
        'status',
        'cancel_reason',
        'item_notes',
        'batches_data',
    ];

    protected $casts = [
        'batches_data' => 'array',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
