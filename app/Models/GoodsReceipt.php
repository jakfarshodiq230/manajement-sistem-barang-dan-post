<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity, \App\Traits\ScopedByBranch;

    public function getBranchScopeType()
    {
        return 'purchase_order_id';
    }

    protected $fillable = ['uuid', 'validated_by', 'validated_at', 'approved_by', 'approved_at', 'approval_status', 
        'receipt_number',
        'purchase_order_id',
        'user_id',
        'date',
        'notes',
        'photos',
    ];

    protected $casts = [
        'photos' => 'array',
        'date' => 'date',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }


    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function items()
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }



    public function validator() { return $this->belongsTo(\App\Models\User::class, 'validated_by'); }
    public function approver() { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }
}