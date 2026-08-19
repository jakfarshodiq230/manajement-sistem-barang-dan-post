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
        $query = Employee::with(['branch', 'user']);
        
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
        ]);

        DB::beginTransaction();
        try {
            $employeeData = $request->except(['role_id']);
            $employee = Employee::create($employeeData);

            // If a role is provided, create a user account
            if ($request->filled('role_id')) {
                // Email is required to create a user account for login
                if (empty($request->email)) {
                    throw new \Exception('Email wajib diisi jika karyawan diberikan Hak Akses (Role).');
                }
                
                // Check if user with email already exists
                $user = User::where('email', $request->email)->first();
                if (!$user) {
                    $defaultPassword = 'password';
                    if (!empty($request->birth_date)) {
                        $defaultPassword = date('dmY', strtotime($request->birth_date));
                    }
                    
                    $user = User::create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'password' => Hash::make($defaultPassword),
                    ]);
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
        return Employee::findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        if (!request()->user()->can('Manajemen Karyawan Write')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::findOrFail($id);
            $employeeData = $request->except(['role_id']);
            $employee->update($employeeData);

            if ($request->filled('role_id')) {
                if (empty($request->email)) {
                    throw new \Exception('Email wajib diisi jika karyawan diberikan Hak Akses (Role).');
                }

                $user = User::where('email', $request->email)->first();
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
                    ]);
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
        
        // Instead of hard delete, maybe just set status to Diberhentikan or Resign, but let's allow hard delete if no related transactions
        $employee->delete();
        
        return response()->json(['message' => 'Karyawan berhasil dihapus.']);
    }
}
