<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPointHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'sale_id',
        'type', // earned, redeemed, adjusted
        'points',
        'description',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
