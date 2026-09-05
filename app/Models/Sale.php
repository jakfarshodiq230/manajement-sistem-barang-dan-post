<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity, \App\Traits\ScopedByBranch;

    protected $fillable = ['uuid', 'validated_by', 'validated_at', 'approved_by', 'approved_at', 'approval_status', 
        'invoice_number',
        'customer_id',
        'branch_id',
        'user_id',
        'approved_by',
        'date',
        'subtotal',
        'discount',
        'total_tax',
        'total_amount',
        'status',
        'notes',
        'payment_method',
        'bank_account_id',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'transfer_phone_number',
        'payment_proof',
        'paid_amount',
        'change_amount',
        'due_date',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }



    public function validator() { return $this->belongsTo(\App\Models\User::class, 'validated_by'); }
    public function approver() { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function receivable()
    {
        return $this->hasOne(Receivable::class);
    }
}