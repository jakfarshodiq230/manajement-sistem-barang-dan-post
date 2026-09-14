<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'branch_id',
        'period_month',
        'period_year',
        'base_salary',
        'total_attendance_days',
        'overtime_pay',
        'allowances',
        'deductions',
        'bonus',
        'loan_deduction',
        'net_salary',
        'status',
        'paid_at',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
