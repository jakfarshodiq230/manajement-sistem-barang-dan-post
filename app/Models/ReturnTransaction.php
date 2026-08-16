<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnTransaction extends Model
{
    use HasFactory, \App\Traits\ScopedByBranch;

    protected $guarded = [];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }




    public function items()
    {
        return $this->hasMany(ReturnItem::class);
    }


    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'reference_id');
    }


    public function sale()
    {
        return $this->belongsTo(Sale::class, 'reference_id');
    }



    public function validator() { return $this->belongsTo(\App\Models\User::class, 'validated_by'); }
    public function approver() { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }
}