<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayablePayment extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity;

    protected $fillable = [
        'payment_number',
        'payable_statement_id',
        'payable_id',
        'payment_date',
        'amount',
        'payment_method',
        'bank_account_id',
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

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function payableStatement()
    {
        return $this->belongsTo(PayableStatement::class, 'payable_statement_id');
    }

    public function statement()
    {
        return $this->belongsTo(PayableStatement::class, 'payable_statement_id');
    }

    public function payable()
    {
        return $this->belongsTo(Payable::class);
    }

    public function supplierCredit()
    {
        return $this->belongsTo(SupplierCredit::class);
    }

    public function paymentItems()
    {
        return $this->hasMany(PayablePaymentItem::class, 'payable_payment_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
