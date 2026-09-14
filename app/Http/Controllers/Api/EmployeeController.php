<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['branch', 'user', 'position', 'deductionTypes']);
        
        $search = $request->query('search');
        $branchId = $request->query('branch_id');
        $status = $request->query('status');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        $page = $request->query('page', 1);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('branch', function($b) use ($search) {
                      $b->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        if ($status && $status !== 'all') {
            if (in_array(strtolower($status), ['aktif', 'active', '1'])) {
                $query->where(function($q) {
                    $q->whereIn('status', ['Aktif', 'aktif', 'active', 'Active', '1'])
                      ->orWhereNull('status');
                });
            } else {
                $query->whereNotIn('status', ['Aktif', 'aktif', 'active', 'Active', '1']);
            }
        }
        
        $query->orderBy('name', 'asc');

        if ($request->has('itemsPerPage')) {
            if ($itemsPerPage == -1) {
                $employees = $query->get();
                $paginated = null;
            } else {
                $paginated = $query->paginate($itemsPerPage, ['*'], 'page', $page);
                $employees = $paginated->items();
            }
            
            $formatted = collect($employees)->map(function ($emp) {
                // Get role assignments for this user if user exists
                $roles = [];
                if ($emp->user_id) {
                    $roles = DB::table('model_has_roles as mhr')
                        ->join('roles', 'mhr.role_id', '=', 'roles.id')
                        ->where('mhr.model_type', 'App\\Models\\User')
                        ->where('mhr.model_id', $emp->user_id)
                        ->where('mhr.branch_id', $emp->branch_id)
                        ->pluck('roles.id')
                        ->toArray();
                }

                $isActive = in_array(strtolower((string)($emp->status ?? 'active')), ['aktif', 'active', '1']);
                
                return [
                    'id' => $emp->id,
                    'name' => $emp->name,
                    'nik' => $emp->nik,
                    'birth_place' => $emp->birth_place,
                    'birth_date' => $emp->birth_date,
                    'gender' => $emp->gender,
                    'religion' => $emp->religion,
                    'marital_status' => $emp->marital_status,
                    'education' => $emp->education,
                    'phone' => $emp->phone,
                    'email' => $emp->email,
                    'address' => $emp->address,
                    'emergency_contact_name' => $emp->emergency_contact_name,
                    'emergency_contact_phone' => $emp->emergency_contact_phone,
                    'branch_id' => $emp->branch_id,
                    'branch_name' => $emp->branch ? $emp->branch->name : null,
                    'user_id' => $emp->user_id,
                    'joined_date' => $emp->joined_date,
                    'status' => $isActive ? 'Aktif' : ($emp->status ?? 'Nonaktif'),
                    'role_id' => count($roles) > 0 ? $roles[0] : null,
                    'position_id' => $emp->position_id,
                    'position_name' => $emp->position ? $emp->position->name : null,
                    'custom_base_salary' => $emp->custom_base_salary,
                    'custom_allowance' => $emp->custom_allowance,
                    'custom_deduction' => $emp->custom_deduction,
                    'bank_name' => $emp->bank_name,
                    'bank_account_number' => $emp->bank_account_number,
                    'attendance_machine_id' => $emp->attendance_machine_id,
                ];
            });

            $totalActive = Employee::where(function($q) {
                $q->whereIn('status', ['Aktif', 'aktif', 'active', 'Active', '1'])
                  ->orWhereNull('status');
            })->count();
            $totalWithUser = Employee::whereNotNull('user_id')->count();
            $totalAll = Employee::count();

            $response = [
                'data' => $formatted,
                'summary' => [
                    'total' => $totalAll,
                    'active' => $totalActive,
                    'with_user' => $totalWithUser,
                ],
            ];

            if ($paginated) {
                $response['current_page'] = $paginated->currentPage();
                $response['last_page'] = $paginated->lastPage();
                $response['per_page'] = $paginated->perPage();
                $response['total'] = $paginated->total();
            }

            return response()->json($response);
        }

        // Backward compatibility
        $employees = $query->get();
        $formatted = $employees->map(function ($emp) {
            // Get role assignments for this user if user exists
            $roles = [];
            if ($emp->user_id) {
                $roles = DB::table('model_has_roles as mhr')
                    ->join('roles', 'mhr.role_id', '=', 'roles.id')
                    ->where('mhr.model_type', 'App\\Models\\User')
                    ->where('mhr.model_id', $emp->user_id)
                    ->where('mhr.branch_id', $emp->branch_id)
                    ->pluck('roles.id')
                    ->toArray();
            }
            
            return [
                'id' => $emp->id,
                'name' => $emp->name,
                'nik' => $emp->nik,
                'birth_place' => $emp->birth_place,
                'birth_date' => $emp->birth_date,
                'gender' => $emp->gender,
                'religion' => $emp->religion,
                'marital_status' => $emp->marital_status,
                'education' => $emp->education,
                'phone' => $emp->phone,
                'email' => $emp->email,
                'address' => $emp->address,
                'emergency_contact_name' => $emp->emergency_contact_name,
                'emergency_contact_phone' => $emp->emergency_contact_phone,
                'branch_id' => $emp->branch_id,
                'branch_name' => $emp->branch ? $emp->branch->name : null,
                'user_id' => $emp->user_id,
                'joined_date' => $emp->joined_date,
                'status' => $emp->status,
                'role_id' => count($roles) > 0 ? $roles[0] : null,
                'position_id' => $emp->position_id,
                'position_name' => $emp->position ? $emp->position->name : null,
                'custom_base_salary' => $emp->custom_base_salary,
                'custom_allowance' => $emp->custom_allowance,
                'custom_deduction' => $emp->custom_deduction,
                'bank_name' => $emp->bank_name,
                'bank_account_number' => $emp->bank_account_number,
                'attendance_machine_id' => $emp->attendance_machine_id,
            ];
        });
        
        return response()->json($formatted);
    }

    public function store(Request $request)
    {
        if (!request()->user()->can('Manajemen Karyawan Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'email' => 'nullable|email',
            'nik' => 'nullable|string',
            'position_id' => 'nullable|exists:positions,id',
            'status' => 'required|string',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'attendance_machine_id' => 'nullable|string',
            'custom_base_salary' => 'nullable|numeric',
            'custom_allowance' => 'nullable|numeric',
            'custom_deduction' => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        try {
            $employeeData = $request->except(['role_id', 'deduction_type_ids']);
            $employee = Employee::create($employeeData);

            if ($request->has('deduction_type_ids')) {
                $employee->deductionTypes()->sync($request->deduction_type_ids);
            }

            // If a role is provided, create a user account
            if ($request->filled('role_id')) {
                // Email is required to create a user account for login
                if (empty($request->email)) {
                    throw new \Exception('Email wajib diisi jika karyawan diberikan Hak Akses (Role).');
                }
                
                // Check if user with email already exists
                $user = User::where('email', $request->email)->first();
                if ($user) {
                    throw new \Exception('Email sudah terdaftar pada pengguna lain. Gunakan email yang berbeda.');
                }

                if (!$user) {
                    $defaultPassword = 'password';
                    if (!empty($request->birth_date)) {
                        $defaultPassword = date('dmY', strtotime($request->birth_date));
                    }
                    
                    $user = User::create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'password' => Hash::make($defaultPassword),
                        'branch_id' => $request->branch_id,
                        'status' => 'Aktif',
                    ]);

                    // Send Email Verification Notification
                    $user->sendEmailVerificationNotification();
                }
                
                $employee->user_id = $user->id;
                $employee->save();
                
                // Assign role for the specific branch
                DB::table('model_has_roles')->updateOrInsert(
                    [
                        'model_type' => 'App\\Models\\User',
                        'model_id' => $user->id,
                        'branch_id' => $request->branch_id,
                    ],
                    [
                        'role_id' => $request->role_id,
                    ]
                );
            }

            DB::commit();
            return response()->json(['message' => 'Karyawan berhasil ditambahkan.', 'data' => $employee]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(string $id)
    {
        return Employee::with(['branch', 'user', 'position', 'deductionTypes'])->findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        if (!request()->user()->can('Manajemen Karyawan Write')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'email' => 'nullable|email',
            'nik' => 'nullable|string',
            'position_id' => 'nullable|exists:positions,id',
            'status' => 'required|string',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'attendance_machine_id' => 'nullable|string',
            'custom_base_salary' => 'nullable|numeric',
            'custom_allowance' => 'nullable|numeric',
            'custom_deduction' => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::findOrFail($id);
            $employeeData = $request->except(['role_id', 'deduction_type_ids']);
            $employee->update($employeeData);

            if ($request->has('deduction_type_ids')) {
                $employee->deductionTypes()->sync($request->deduction_type_ids);
            }

            if ($request->filled('role_id')) {
                if (empty($request->email)) {
                    throw new \Exception('Email wajib diisi jika karyawan diberikan Hak Akses (Role).');
                }

                $user = User::where('email', $request->email)->first();
                
                if ($user && $user->id !== $employee->user_id) {
                    throw new \Exception('Email sudah terdaftar pada pengguna lain. Gunakan email yang berbeda.');
                }

                if (!$user && $employee->user_id) {
                    $user = User::find($employee->user_id);
                    $user->update(['email' => $request->email, 'name' => $request->name]);
                } elseif (!$user) {
                    $defaultPassword = 'password';
                    if (!empty($request->birth_date)) {
                        $defaultPassword = date('dmY', strtotime($request->birth_date));
                    }
                    
                    $user = User::create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'password' => Hash::make($defaultPassword),
                        'branch_id' => $request->branch_id,
                        'status' => 'Aktif',
                    ]);

                    // Send Email Verification Notification
                    $user->sendEmailVerificationNotification();
                }
                
                $employee->user_id = $user->id;
                $employee->save();

                DB::table('model_has_roles')->updateOrInsert(
                    [
                        'model_type' => 'App\\Models\\User',
                        'model_id' => $user->id,
                        'branch_id' => $request->branch_id,
                    ],
                    [
                        'role_id' => $request->role_id,
                    ]
                );
            } else {
                // Remove role for this branch if role_id is cleared
                if ($employee->user_id) {
                    DB::table('model_has_roles')
                        ->where('model_type', 'App\\Models\\User')
                        ->where('model_id', $employee->user_id)
                        ->where('branch_id', $request->branch_id)
                        ->delete();
                }
            }

            DB::commit();
            return response()->json(['message' => 'Karyawan berhasil diperbarui.', 'data' => $employee]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(string $id)
    {
        if (!request()->user()->can('Manajemen Karyawan Delete')) {
            abort(403, 'Unauthorized action.');
        }

        $employee = Employee::findOrFail($id);
        
        // Soft delete (Ubah status ke Nonaktif)
        $employee->status = 'Nonaktif';
        $employee->save();
        
        // Remove roles if any
        if ($employee->user_id) {
            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $employee->user_id)
                ->delete();
        }
        
        return response()->json(['message' => 'Karyawan berhasil dinonaktifkan (Soft Delete).']);
    }

    /**
     * Download import template CSV
     */
    public function importTemplate()
    {
        $branches = \App\Models\Branch::orderBy('name')->get();
        
        $csvContent = "Nama Lengkap (Wajib),NIK / No KTP (Wajib),No HP (Wajib),Email,Jenis Kelamin (L/P),Tempat Lahir,Tanggal Lahir (YYYY-MM-DD),Agama,Status Pernikahan,Pendidikan,Alamat Lengkap,Nama Cabang (Wajib),Tanggal Bergabung (YYYY-MM-DD),Status (Aktif/Nonaktif),Kontak Darurat Nama,Kontak Darurat No HP\n";
        
        // Add example rows
        $branchExample = $branches->first() ? $branches->first()->name : 'Cabang Utama';
        $csvContent .= "Ahmad Fauzi,3201010101010001,081234567890,ahmad@email.com,L,Bandung,1995-03-15,Islam,Menikah,S1,Jl. Merdeka No. 10,$branchExample,2024-01-15,Aktif,Siti Aminah,081298765432\n";
        $csvContent .= "Dewi Lestari,3201020202020002,085612345678,dewi@email.com,P,Jakarta,1998-07-22,Kristen,Belum Menikah,SMA,Jl. Sudirman No. 5,$branchExample,2024-06-01,Aktif,,\n";
        
        return response()->json([
            'csv' => $csvContent,
        ]);
    }

    /**
     * Import employees from CSV file
     */
    public function importEmployees(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), "r");
        
        $header = true;
        $count = 0;
        $errors = [];
        
        // Pre-load branches for matching
        $branches = \App\Models\Branch::all()->keyBy(function ($b) {
            return strtolower(trim($b->name));
        });

        DB::beginTransaction();
        try {
            $rowNum = 1;
            while (($row = fgetcsv($handle, 4000, ",")) !== FALSE) {
                if ($header) {
                    $header = false;
                    $rowNum++;
                    continue;
                }
                $rowNum++;
                
                // Skip empty rows
                if (!isset($row[0]) || trim($row[0]) === '') continue;

                $name       = trim($row[0] ?? '');
                $nik        = trim($row[1] ?? '');
                $phone      = trim($row[2] ?? '');
                $email      = trim($row[3] ?? '') ?: null;
                $gender     = strtoupper(trim($row[4] ?? 'L'));
                $birthPlace = trim($row[5] ?? '') ?: null;
                $birthDate  = trim($row[6] ?? '') ?: null;
                $religion   = trim($row[7] ?? '') ?: null;
                $marital    = trim($row[8] ?? '') ?: null;
                $education  = trim($row[9] ?? '') ?: null;
                $address    = trim($row[10] ?? '') ?: null;
                $branchName = strtolower(trim($row[11] ?? ''));
                $joinedDate = trim($row[12] ?? '') ?: null;
                $status     = trim($row[13] ?? 'Aktif');
                $emergName  = trim($row[14] ?? '') ?: null;
                $emergPhone = trim($row[15] ?? '') ?: null;

                // Validate required fields
                if (empty($name) || empty($nik) || empty($phone)) {
                    $errors[] = "Baris $rowNum: Nama, NIK, dan No HP wajib diisi.";
                    continue;
                }

                // Match branch
                $branch = $branches->get($branchName);
                if (!$branch) {
                    $errors[] = "Baris $rowNum ($name): Cabang '$branchName' tidak ditemukan di sistem.";
                    continue;
                }

                // Normalize gender
                if (!in_array($gender, ['L', 'P'])) {
                    $gender = 'L';
                }

                // Normalize status
                $statusNormalized = in_array(strtolower($status), ['aktif', 'active', '1']) ? 'Aktif' : 'Nonaktif';

                Employee::updateOrCreate(
                    ['nik' => $nik],
                    [
                        'name'                   => $name,
                        'phone'                  => $phone,
                        'email'                  => $email,
                        'gender'                 => $gender,
                        'birth_place'            => $birthPlace,
                        'birth_date'             => $birthDate,
                        'religion'               => $religion,
                        'marital_status'         => $marital,
                        'education'              => $education,
                        'address'                => $address,
                        'branch_id'              => $branch->id,
                        'joined_date'            => $joinedDate,
                        'status'                 => $statusNormalized,
                        'emergency_contact_name' => $emergName,
                        'emergency_contact_phone'=> $emergPhone,
                    ]
                );
                $count++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return response()->json(['message' => 'Gagal mengimpor: ' . $e->getMessage()], 500);
        }
        
        fclose($handle);
        
        $message = "$count data karyawan berhasil diimpor.";
        if (count($errors) > 0) {
            $message .= ' ' . count($errors) . ' baris dilewati: ' . implode(' | ', array_slice($errors, 0, 5));
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'imported' => $count,
            'errors' => $errors,
        ]);
    }
}
