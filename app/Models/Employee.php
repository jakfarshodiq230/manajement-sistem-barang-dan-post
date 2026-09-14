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
        'position_id',
        'custom_base_salary',
        'custom_allowance',
        'custom_deduction',
        'bank_name',
        'bank_account_number',
        'attendance_machine_id',
    ];
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function deductionTypes()
    {
        return $this->belongsToMany(DeductionType::class, 'deduction_type_employee');
    }
}
