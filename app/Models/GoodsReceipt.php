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

    protected $fillable = [
        'uuid',
        'validated_by',
        'validated_at',
        'approved_by',
        'approved_at',
        'approval_status',
        'receipt_number',
        'invoice_number_supplier',
        'sales_name',
        'purchase_order_id',
        'user_id',
        'date',
        'received_date',
        'due_date',
        'tax_type',
        'tax_percentage',
        'dpp_amount',
        'tax_amount',
        'subtotal_bruto',
        'extra_discount',
        'total_amount',
        'notes',
        'rejection_reason',
        'photos',
    ];

    protected $casts = [
        'photos' => 'array',
        'date' => 'date',
        'received_date' => 'date',
        'due_date' => 'date',
        'validated_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }


    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function branch()
    {
        return $this->hasOneThrough(Branch::class, PurchaseOrder::class, 'id', 'id', 'purchase_order_id', 'branch_id');
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
}