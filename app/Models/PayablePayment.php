<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayablePayment extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity;

    protected $fillable = [
        'payment_number',
        'payable_id',
        'payment_date',
        'amount',
        'payment_method',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'reference_number',
        'proof_file',
        'supplier_credit_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }

    public function payable()
    {
        return $this->belongsTo(Payable::class);
    }

    public function supplierCredit()
    {
        return $this->belongsTo(SupplierCredit::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
