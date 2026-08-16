<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'width',
        'is_active',
        'is_default',
        'margin_top',
        'margin_bottom',
        'margin_left',
        'margin_right',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];
}
