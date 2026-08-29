<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivablePayment extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity;

    protected $fillable = [
        'receivable_id',
        'payment_date',
        'amount',
        'payment_method',
        'bank_account_id',
        'payment_proof',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'transfer_phone_number',
        'user_id',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function receivable()
    {
        return $this->belongsTo(Receivable::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
