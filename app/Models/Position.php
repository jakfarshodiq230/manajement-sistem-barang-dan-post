<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'name',
        'department',
        'level',
        'base_salary',
        'default_allowance',
        'default_deduction',
        'description',
        'status',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
