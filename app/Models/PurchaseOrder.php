<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity, \App\Traits\ScopedByBranch;

    protected $fillable = ['uuid', 'validated_by', 'validated_at', 'approved_by', 'approved_at', 'approval_status', 
        'po_number',
        'invoice_number_supplier',
        'branch_id',
        'supplier_id',
        'user_id',
        'date',
        'due_date',
        'status',
        'total_amount',
        'tax_type',
        'tax_percentage',
        'dpp_amount',
        'tax_amount',
        'subtotal_bruto',
        'extra_discount',
        'notes',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }


    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }



    public function goodsReceipt()
    {
        return $this->hasOne(GoodsReceipt::class);
    }

    public function validator() { return $this->belongsTo(\App\Models\User::class, 'validated_by'); }
    public function approver() { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }
}