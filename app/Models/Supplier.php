<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity;

    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'cutoff_day',
        'is_active',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }
}
