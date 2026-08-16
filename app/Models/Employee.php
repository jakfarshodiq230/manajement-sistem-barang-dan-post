<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory, \App\Traits\ScopedByBranch;
    
    protected $fillable = [
        'name',
        'nik',
        'birth_place',
        'birth_date',
        'gender',
        'religion',
        'marital_status',
        'education',
        'phone',
        'email',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'branch_id',
        'user_id',
        'joined_date',
        'status',
    ];
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
