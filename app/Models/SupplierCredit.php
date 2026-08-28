<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierCredit extends Model
{
    use HasFactory, \App\Traits\ScopedByBranch;

    protected $fillable = [
        'credit_number',
        'supplier_id',
        'branch_id',
        'return_transaction_id',
        'purchase_order_id',
        'amount',
        'used_amount',
        'remaining_amount',
        'status',
        'notes',
        'created_by',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function returnTransaction()
    {
        return $this->belongsTo(ReturnTransaction::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
