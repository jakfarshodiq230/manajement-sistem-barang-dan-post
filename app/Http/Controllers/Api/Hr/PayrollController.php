<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->header('X-Branch-ID') ?: $request->branch_id;
        $query = Payroll::with('employee');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        if ($request->month) {
            $query->where('period_month', $request->month);
        }
        
        if ($request->year) {
            $query->where('period_year', $request->year);
        }

        $payrolls = $query->orderBy('period_year', 'desc')->orderBy('period_month', 'desc')->get();
        return response()->json(['data' => $payrolls]);
    }

    /**
     * Get employees list prepared for payroll generation
     */
    public function getEmployeesForPayroll(Request $request)
    {
        $branchId = $request->header('X-Branch-ID');
        if (!$branchId && $request->branch_id) {
            $branchId = $request->branch_id;
        }

        $query = Employee::with('position')
            ->where(function($q) {
                $q->where('status', 'Aktif')->orWhere('status', 'active')->orWhere('status', '1');
            });

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $employees = $query->get()->map(function ($emp) {
            // Determine default allowance/deduction based on position or custom override
            $allowance = $emp->custom_allowance !== null ? $emp->custom_allowance : ($emp->position ? $emp->position->default_allowance : 0);
            $deduction = $emp->custom_deduction !== null ? $emp->custom_deduction : ($emp->position ? $emp->position->default_deduction : 0);
            $baseSalary = $emp->custom_base_salary !== null ? $emp->custom_base_salary : ($emp->position ? $emp->position->base_salary : 0);

            return [
                'employee_id' => $emp->id,
                'name' => $emp->name,
                'nik' => $emp->nik,
                'attendance_machine_id' => $emp->attendance_machine_id,
                'position' => $emp->position ? $emp->position->name : null,
                'base_salary' => $baseSalary,
                'total_attendance_days' => 0, // Will be filled by HR or CSV import
                'allowances' => $allowance, // This is default allowance (can be edited by HR)
                'deductions' => $deduction, // Default deduction
                'bonus' => 0, // Additional dynamic bonus
                'net_salary' => $baseSalary + $allowance - $deduction,
            ];
        });

        return response()->json(['data' => $employees]);
    }

    /**
     * Generate / Save payroll
     */
    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|string',
            'year' => 'required|string',
            'payrolls' => 'required|array',
            'branch_id' => 'required|exists:branches,id'
        ]);

        try {
            DB::beginTransaction();

            $month = $request->month;
            $year = $request->year;
            $branchId = $request->branch_id;

            foreach ($request->payrolls as $row) {
                // Calculate net salary
                $base = $row['base_salary'] ?? 0;
                $allowance = $row['allowances'] ?? 0;
                $bonus = $row['bonus'] ?? 0;
                $deduction = $row['deductions'] ?? 0;
                
                $net = $base + $allowance + $bonus - $deduction;

                Payroll::updateOrCreate(
                    [
                        'employee_id' => $row['employee_id'],
                        'period_month' => $month,
                        'period_year' => $year,
                    ],
                    [
                        'branch_id' => $branchId,
                        'base_salary' => $base,
                        'total_attendance_days' => $row['total_attendance_days'] ?? 0,
                        'allowances' => $allowance,
                        'bonus' => $bonus,
                        'deductions' => $deduction,
                        'net_salary' => $net,
                        'status' => 'approved',
                        'approved_by' => auth()->id() ?? 1,
                    ]
                );
            }

            DB::commit();

            return response()->json(['message' => 'Penggajian berhasil disimpan dan diterbitkan.']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan penggajian: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Parse CSV Attendance
     */
    public function importAttendance(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        
        $data = [];
        if (($handle = fopen($file->getRealPath(), "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Assuming CSV format: ID Mesin, Nama, Total Kehadiran
                // Adjust based on typical attendance machine export
                if (count($row) >= 3) {
                    $data[] = [
                        'machine_id' => $row[0],
                        'name' => $row[1],
                        'total_days' => (int) $row[2]
                    ];
                }
            }
            fclose($handle);
        }

        return response()->json([
            'message' => 'Data absensi berhasil dibaca',
            'data' => $data
        ]);
    }
}
