<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'amount',
        'is_percentage',
        'status',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_percentage' => 'boolean',
    ];
}
