<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::with(['employee', 'branch']);

        if ($request->has('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year);
        }

        $itemsPerPage = $request->get('itemsPerPage', 15);
        if ($itemsPerPage == -1) {
            return response()->json($query->orderBy('date', 'desc')->get());
        }

        return response()->json($query->orderBy('date', 'desc')->paginate($itemsPerPage));
    }

    public function summary(Request $request)
    {
        $month = $request->get('month', date('n'));
        $year = $request->get('year', date('Y'));
        
        $query = Employee::with('position')->whereIn('status', ['Aktif', 'active', '1']);
        
        if ($request->has('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        $employees = $query->get();
        $employeeIds = $employees->pluck('id')->toArray();

        $attendances = Attendance::whereIn('employee_id', $employeeIds)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $summary = $employees->map(function ($emp) use ($attendances) {
            $empAttendances = $attendances->where('employee_id', $emp->id);
            return [
                'employee_id' => $emp->id,
                'name' => $emp->name,
                'nik' => $emp->nik,
                'branch_id' => $emp->branch_id,
                'position' => $emp->position ? $emp->position->name : null,
                'total_hadir' => $empAttendances->where('status', 'hadir')->count(),
                'total_izin' => $empAttendances->where('status', 'izin')->count(),
                'total_sakit' => $empAttendances->where('status', 'sakit')->count(),
                'total_alpha' => $empAttendances->where('status', 'alpha')->count(),
                'total_cuti' => $empAttendances->where('status', 'cuti')->count(),
            ];
        });

        return response()->json(['data' => $summary]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        
        $count = 0;
        if (($handle = fopen($file->getRealPath(), "r")) !== FALSE) {
            // Assume format: Machine_ID, Date (YYYY-MM-DD), Clock_In, Clock_Out, Status (hadir/izin/sakit/alpha)
            $header = fgetcsv($handle, 1000, ",");
            
            \Illuminate\Support\Facades\DB::beginTransaction();
            try {
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($row) >= 5) {
                    $machineId = $row[0];
                    $date = $row[1];
                    $clockIn = $row[2];
                    $clockOut = $row[3];
                    $status = strtolower($row[4]);
                    
                    $employee = Employee::where('attendance_machine_id', $machineId)->first();
                    
                    if ($employee) {
                        Attendance::updateOrCreate(
                            [
                                'employee_id' => $employee->id,
                                'date' => $date
                            ],
                            [
                                'branch_id' => $employee->branch_id,
                                'clock_in' => $clockIn,
                                'clock_out' => $clockOut,
                                'status' => in_array($status, ['hadir', 'izin', 'sakit', 'alpha', 'cuti']) ? $status : 'hadir',
                            ]
                        );
                        $count++;
                    }
                }
            } // Close while loop
                
            \Illuminate\Support\Facades\DB::commit();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            fclose($handle);
            return response()->json(['message' => 'Gagal mengimpor presensi: ' . $e->getMessage()], 500);
        }
            fclose($handle);
        }

        return response()->json([
            'message' => "Berhasil mengimpor $count data absensi",
        ]);
    }

    public function importTemplate(Request $request)
    {
        $branchId = $request->get('branch_id');
        $month = $request->get('month', date('n'));
        $year = $request->get('year', date('Y'));
        
        // Menambahkan kolom Nama Karyawan di akhir agar tidak merusak urutan import (index 0 s/d 4)
        $csvContent = "ID Mesin Absensi,Tanggal (YYYY-MM-DD),Jam Masuk (HH:MM),Jam Keluar (HH:MM),Status (hadir/izin/sakit/alpha/cuti),Nama Karyawan (Hanya Referensi)\n";
        
        if ($branchId && $branchId !== 'all') {
            $employees = Employee::where('branch_id', $branchId)
                            ->whereIn('status', ['Aktif', 'active', '1'])
                            ->get();
            
            $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
            
            foreach ($employees as $emp) {
                // Gunakan attendance_machine_id jika ada, jika tidak fallback ke NIK atau ID
                $machineId = $emp->attendance_machine_id ?: ($emp->nik ?: $emp->id);
                
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $csvContent .= "{$machineId},{$dateStr},,,,{$emp->name}\n";
                }
            }
        } else {
            // Fallback jika tidak ada cabang yang dipilih
            $csvContent .= "101," . date('Y-m-d') . ",,,,Contoh Karyawan\n";
        }
        
        return response()->json([
            'csv' => $csvContent,
        ]);
    }
}
