<?php

namespace App\Http\Controllers\Api;

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
        $month = $request->get('month', date('n'));
        $year = $request->get('year', date('Y'));
        
        $branchId = $request->header('X-Branch-ID');
        if (!$branchId && $request->branch_id) {
            $branchId = $request->branch_id;
        }

        $query = Employee::with(['position', 'deductionTypes'])
            ->where(function($q) {
                $q->where('status', 'Aktif')->orWhere('status', 'active')->orWhere('status', '1');
            });

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $employeesList = $query->get();
        $employeeIds = $employeesList->pluck('id')->toArray();
        
        // Fetch attendances for this month
        $attendances = \App\Models\Attendance::whereIn('employee_id', $employeeIds)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('status', 'hadir')
            ->get();

        $employees = $employeesList->map(function ($emp) use ($attendances) {
            // Base configuration
            $baseSalary = $emp->custom_base_salary !== null ? $emp->custom_base_salary : ($emp->position ? $emp->position->base_salary : 0);
            $allowance = $emp->custom_allowance !== null ? $emp->custom_allowance : ($emp->position ? $emp->position->default_allowance : 0);
            $deduction = $emp->custom_deduction !== null ? $emp->custom_deduction : ($emp->position ? $emp->position->default_deduction : 0);
            
            // Total Attendance
            $totalDays = $attendances->where('employee_id', $emp->id)->count();
            
            // Apply Master Potongan/Tunjangan mapped to this employee
            if ($emp->deductionTypes) {
                foreach ($emp->deductionTypes as $modifier) {
                    if ($modifier->status === 'Aktif') {
                        $modAmount = $modifier->amount;
                        if ($modifier->is_percentage) {
                            $modAmount = ($modifier->amount / 100) * $baseSalary;
                        }
                        
                        if ($modifier->type === 'tunjangan') {
                            $allowance += $modAmount;
                        } else if ($modifier->type === 'potongan') {
                            $deduction += $modAmount;
                        }
                    }
                }
            }

            return [
                'employee_id' => $emp->id,
                'name' => $emp->name,
                'nik' => $emp->nik,
                'attendance_machine_id' => $emp->attendance_machine_id,
                'position' => $emp->position ? $emp->position->name : null,
                'base_salary' => $baseSalary,
                'total_attendance_days' => $totalDays,
                'allowances' => $allowance,
                'deductions' => $deduction,
                'bonus' => 0,
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

    /**
     * Download Payroll PDF
     */
    public function downloadPdf($id)
    {
        $payroll = Payroll::with(['employee.deductionTypes', 'employee.position', 'branch.owner'])->findOrFail($id);
        
        // Calculate dynamic deductions breakdown
        $baseSalary = $payroll->employee->custom_base_salary ?? ($payroll->employee->position->base_salary ?? 0);
        $dynamicDeductions = [];
        $totalDynamic = 0;
        
        $baseDeduction = $payroll->employee->custom_deduction ?? ($payroll->employee->position->default_deduction ?? 0);
        if ($baseDeduction > 0) {
            $dynamicDeductions[] = ['name' => 'Potongan Jabatan/Dasar', 'amount' => $baseDeduction];
            $totalDynamic += $baseDeduction;
        }

        if ($payroll->employee && $payroll->employee->deductionTypes) {
            foreach ($payroll->employee->deductionTypes as $modifier) {
                if ($modifier->status === 'Aktif' && $modifier->type === 'potongan') {
                    $modAmount = $modifier->amount;
                    if ($modifier->is_percentage) {
                        $modAmount = ($modifier->amount / 100) * $baseSalary;
                    }
                    $dynamicDeductions[] = ['name' => $modifier->name, 'amount' => $modAmount];
                    $totalDynamic += $modAmount;
                }
            }
        }
        
        // Ensure totals match historical saved deduction
        if ($payroll->deductions != $totalDynamic) {
            $diff = $payroll->deductions - $totalDynamic;
            if ($diff != 0) {
                $dynamicDeductions[] = ['name' => 'Penyesuaian Potongan', 'amount' => $diff];
            }
        }

        $data = [
            'payroll' => $payroll,
            'branch' => $payroll->branch,
            'dynamicDeductions' => $dynamicDeductions,
            'qrCode' => base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(100)->generate(url('/api/apps/payrolls/' . $payroll->id . '/pdf'))),
            'document' => $payroll,
            'type' => 'payroll'
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.payroll', $data);
        $fileName = 'Slip_Gaji_' . str_replace(' ', '_', $payroll->employee->name ?? 'Karyawan') . '_' . $payroll->period_month . '_' . $payroll->period_year . '.pdf';
        
        return $pdf->stream($fileName);
    }
}
