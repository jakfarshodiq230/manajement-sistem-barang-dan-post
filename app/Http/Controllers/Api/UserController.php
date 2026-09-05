<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function updatePin(Request $request, $id)
    {
        $admin = $request->user();
        if (!$admin || (!$admin->can('Pengguna Write') && !$admin->can('Pengguna PIN') && !$admin->can('Daftar Pengguna Write'))) {
            return response()->json(['message' => 'Anda tidak memiliki hak akses izin untuk mengubah PIN pengguna.'], 403);
        }

        $user = \App\Models\User::findOrFail($id);

        $pin = $request->pin;
        if (!$pin) {
            $pin = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        }

        $request->validate([
            'pin' => 'nullable|string|digits:6',
        ]);

        $hashedPin = \Illuminate\Support\Facades\Hash::make($pin);
        $user->pos_pin = $hashedPin;
        $user->pin = $hashedPin;
        $user->save();

        return response()->json([
            'message' => 'PIN berhasil diperbarui',
            'pin' => $pin
        ]);
    }

    public function index(Request $request)
    {
        $q = $request->query('q', '');
        $role = $request->query('role');
        $branchId = $request->query('branch_id');
        $plan = $request->query('plan');
        $status = $request->query('status');
        $itemsPerPage = (int) $request->query('itemsPerPage', 10);
        if ($itemsPerPage <= 0) $itemsPerPage = 10;
        $sortBy = $request->query('sortBy', 'id');
        $orderBy = $request->query('orderBy', 'desc');

        $query = User::query();

        // 1. Search Query
        if (!empty($q)) {
            $query->where(function($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        // 2. Filter by Role
        if (!empty($role)) {
            $query->where(function($qBuilder) use ($role) {
                $qBuilder->whereHas('roles', function($rQuery) use ($role) {
                    $rQuery->where('name', $role);
                })->orWhereExists(function($sub) use ($role) {
                    $sub->select(\DB::raw(1))
                        ->from('model_has_roles')
                        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                        ->whereColumn('model_has_roles.model_id', 'users.id')
                        ->where('model_has_roles.model_type', 'App\\Models\\User')
                        ->where('roles.name', $role);
                });
            });
        }

        // 3. Filter by Branch
        if (!empty($branchId)) {
            $query->where(function($bQuery) use ($branchId) {
                $bQuery->where('branch_id', $branchId)
                    ->orWhereExists(function($sub) use ($branchId) {
                        $sub->select(\DB::raw(1))
                            ->from('model_has_roles')
                            ->whereColumn('model_has_roles.model_id', 'users.id')
                            ->where('model_has_roles.model_type', 'App\\Models\\User')
                            ->where('model_has_roles.branch_id', $branchId);
                    });
            });
        }

        // 4. Filter by Status
        if (!empty($status)) {
            $statusLower = strtolower((string)$status);
            if (in_array($statusLower, ['active', 'aktif', '1'])) {
                $query->where(function($sQuery) {
                    $sQuery->where('status', 'Active')
                           ->orWhere('status', 'aktif')
                           ->orWhere('status', 1)
                           ->orWhere('status', '1')
                           ->orWhereNull('status');
                });
            } elseif (in_array($statusLower, ['inactive', 'nonaktif', '0'])) {
                $query->where(function($sQuery) {
                    $sQuery->where('status', 'Inactive')
                           ->orWhere('status', 'nonaktif')
                           ->orWhere('status', 0)
                           ->orWhere('status', '0');
                });
            } elseif (in_array($statusLower, ['pending'])) {
                $query->where('status', 'Pending');
            }
        }

        // 5. Sorting
        $allowedSorts = ['id', 'name', 'email', 'status', 'created_at'];
        $dbSort = in_array($sortBy, $allowedSorts) ? $sortBy : ($sortBy === 'fullName' ? 'name' : 'id');
        $dbOrder = in_array(strtolower($orderBy), ['asc', 'desc']) ? strtolower($orderBy) : 'desc';
        $query->orderBy($dbSort, $dbOrder);

        $paginator = $query->paginate($itemsPerPage);

        $users = $paginator->map(function ($user) {
            // Load all branch-role assignments for this user from pivot table
            $assignments = \DB::table('model_has_roles as mhr')
                ->join('roles', 'mhr.role_id', '=', 'roles.id')
                ->leftJoin('branches', 'mhr.branch_id', '=', 'branches.id')
                ->where('mhr.model_type', 'App\\Models\\User')
                ->where('mhr.model_id', $user->id)
                ->select(
                    'branches.id as branch_id',
                    \DB::raw('COALESCE(branches.name, "Semua Cabang (Global)") as branch_name'),
                    'roles.id as role_id',
                    'roles.name as role_name'
                )
                ->get();

            // Also check standard Spatie user->roles
            $allRoleNames = $assignments->pluck('role_name')->merge($user->roles->pluck('name'))->unique()->values()->toArray();

            $rawStatus = (string) $user->status;
            $statusLabel = 'Active';
            if ($rawStatus === '0' || strtolower($rawStatus) === 'inactive' || strtolower($rawStatus) === 'nonaktif') {
                $statusLabel = 'Inactive';
            } elseif (strtolower($rawStatus) === 'pending') {
                $statusLabel = 'Pending';
            } else {
                $statusLabel = 'Active';
            }

            return [
                'id' => $user->id,
                'pos_pin' => $user->pos_pin,
                'fullName' => $user->name,
                'username' => strtolower(str_replace(' ', '', $user->name)),
                'email' => $user->email,
                'role' => $allRoleNames,
                'assignments' => $assignments->map(fn($a) => [
                    'branch_id'   => $a->branch_id,
                    'branch_name' => $a->branch_name,
                    'role_id'     => $a->role_id,
                    'role_name'   => $a->role_name,
                ])->values()->toArray(),
                'currentPlan' => 'basic',
                'status' => $statusLabel,
                'avatar' => $user->avatar ?: '',
            ];
        });

        $totalActiveUsers = User::where(function($q) {
            $q->where('status', 1)
              ->orWhere('status', '1')
              ->orWhere('status', 'Active')
              ->orWhere('status', 'aktif')
              ->orWhereNull('status');
        })->count();

        $totalBranches = \App\Models\Branch::count();
        $totalRoles = \Spatie\Permission\Models\Role::count();
        $totalUsersCount = User::count();

        return response()->json([
            'users' => $users,
            'totalUsers' => $paginator->total(),
            'stats' => [
                'totalUsers' => $totalUsersCount,
                'activeUsers' => $totalActiveUsers,
                'totalBranches' => $totalBranches,
                'totalRoles' => $totalRoles,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $admin = $request->user();
        if (!$admin || (!$admin->can('Pengguna Create') && !$admin->can('Daftar Pengguna Create') && !$admin->can('manage all') && !$admin->can('*'))) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'fullName' => 'required',
            'email'    => 'required|email|unique:users',
        ]);

        $user = User::create([
            'name'     => $request->fullName,
            'email'    => $request->email,
            'password' => Hash::make('password123'),
        ]);

        // assignments = [{branch_id: 1, role: 'Kasir'}, {branch_id: 2, role: 'Admin Cabang'}]
        if ($request->has('assignments') && is_array($request->assignments)) {
            foreach ($request->assignments as $assignment) {
                $branchId = $assignment['branch_id'] ?? null;
                $roleName = $assignment['role'] ?? null;
                if ($branchId && $roleName) {
                    $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                    if ($role) {
                        \DB::table('model_has_roles')->insertOrIgnore([
                            'role_id'    => $role->id,
                            'model_type' => 'App\\Models\\User',
                            'model_id'   => $user->id,
                            'branch_id'  => $branchId,
                        ]);
                    }
                }
            }
        }

        $recentDevices = $user->tokens()
            ->orderBy('last_used_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($token) {
                // Return structure for Vue component
                // Define active as having been used in the last 15 minutes
                $isActive = $token->last_used_at && $token->last_used_at->diffInMinutes(now()) <= 15;

                return [
                    'browser' => $token->name,
                    'device' => $token->name,
                    'recentActivity' => $token->last_used_at ? $token->last_used_at->diffForHumans() : '-',
                    'isActive' => $isActive,
                ];
            });

        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }

    public function show(User $user)
    {
        $assignments = \DB::table('model_has_roles as mhr')
            ->join('roles', 'mhr.role_id', '=', 'roles.id')
            ->join('branches', 'mhr.branch_id', '=', 'branches.id')
            ->where('mhr.model_type', 'App\\Models\\User')
            ->where('mhr.model_id', $user->id)
            ->select('branches.id as branch_id', 'branches.name as branch_name', 'roles.id as role_id', 'roles.name as role_name')
            ->get();

        $recentSales = \App\Models\Sale::where('user_id', $user->id)
            ->with(['branch'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function ($s) {
                return [
                    'invoice_number' => $s->invoice_number,
                    'date' => $s->date,
                    'total_amount' => $s->total_amount,
                    'status' => $s->status,
                    'branch_name' => $s->branch ? $s->branch->name : '-',
                ];
            });

        $recentDevices = $user->tokens()
            ->orderBy('last_used_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($token) {
                // Return structure for Vue component
                // Define active as having been used in the last 15 minutes
                $isActive = $token->last_used_at && $token->last_used_at->diffInMinutes(now()) <= 15;

                return [
                    'browser' => $token->name,
                    'device' => $token->name,
                    'recentActivity' => $token->last_used_at ? $token->last_used_at->diffForHumans() : '-',
                    'isActive' => $isActive,
                ];
            });

        return response()->json([
            'id'           => $user->id,
            'fullName'     => $user->name,
            'username'     => strtolower(str_replace(' ', '', $user->name)),
            'email'        => $user->email,
            'phone'        => $user->phone,
            'address'      => $user->address,
            'role'         => $assignments->pluck('role_name')->unique()->values()->toArray(),
            'assignments'  => $assignments->map(fn($a) => [
                'branch_id'   => $a->branch_id,
                'branch_name' => $a->branch_name,
                'role_id'     => $a->role_id,
                'role_name'   => $a->role_name,
            ])->values()->toArray(),
            'status'       => $user->status ? 'Active' : 'Inactive',
            'avatar'       => $user->avatar,
            'recentSales'  => $recentSales,
            'recentDevices' => $recentDevices,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $admin = $request->user();
        if (!$admin || (!$admin->can('Pengguna Write') && !$admin->can('Daftar Pengguna Write'))) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'fullName' => 'nullable|string|max:255',
            'name'     => 'nullable|string|max:255',
            'email'    => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:6',
            'pos_pin'  => 'nullable|string|digits:6',
        ]);

        $updateData = [
            'name'    => $request->fullName ?: ($request->name ?: $user->name),
            'email'   => $request->email,
            'phone'   => $request->phone ?? $user->phone,
            'address' => $request->address ?? $user->address,
            'branch_id' => $request->branch_id ?? $user->branch_id,
            'status'  => $request->has('status') ? ($request->status === 'Active' || $request->status === 'aktif' || $request->status == 1 ? 'Active' : 'Inactive') : $user->status,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        if ($request->filled('pos_pin')) {
            $updateData['pos_pin'] = $request->pos_pin;
        }

        $user->update($updateData);

        // Sync primary role if specified
        if ($request->has('role')) {
            $roleName = is_array($request->role) ? ($request->role[0] ?? null) : $request->role;
            if ($roleName) {
                $user->syncRoles([$roleName]);
            }
        }

        // Re-sync all branch-role assignments
        if ($request->has('assignments') && is_array($request->assignments)) {
            // Remove all existing assignments for this user
            \DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $user->id)
                ->delete();

            // Insert new assignments
            foreach ($request->assignments as $assignment) {
                $branchId = $assignment['branch_id'] ?? null;
                $roleName = $assignment['role'] ?? $assignment['role_name'] ?? null;
                if ($branchId && $roleName) {
                    $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                    if ($role) {
                        \DB::table('model_has_roles')->insertOrIgnore([
                            'role_id'    => $role->id,
                            'model_type' => 'App\\Models\\User',
                            'model_id'   => $user->id,
                            'branch_id'  => $branchId,
                        ]);
                    }
                }
            }
        }

        return response()->json(['message' => 'Data pengguna berhasil diperbarui.', 'user' => $user->fresh(['roles', 'branch'])]);
    }

    public function destroy(User $user)
    {
        $admin = request()->user();
        if (!$admin || (!$admin->can('Pengguna Delete') && !$admin->can('Daftar Pengguna Delete') && !$admin->can('manage all') && !$admin->can('*'))) {
            abort(403, 'Unauthorized action.');
        }

        $user->delete();
        $recentDevices = $user->tokens()
            ->orderBy('last_used_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($token) {
                // Return structure for Vue component
                // Define active as having been used in the last 15 minutes
                $isActive = $token->last_used_at && $token->last_used_at->diffInMinutes(now()) <= 15;

                return [
                    'browser' => $token->name,
                    'device' => $token->name,
                    'recentActivity' => $token->last_used_at ? $token->last_used_at->diffForHumans() : '-',
                    'isActive' => $isActive,
                ];
            });

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Update branch & role assignments for a user.
     */
    public function updateAssignments(Request $request, $id)
    {
        $admin = $request->user();
        if (!$admin || (!$admin->can('Pengguna Write') && !$admin->can('Daftar Pengguna Write'))) {
            return response()->json(['message' => 'Anda tidak memiliki hak akses izin untuk mengubah penugasan cabang pengguna.'], 403);
        }

        $user = User::findOrFail($id);

        $request->validate([
            'assignments' => 'required|array',
            'assignments.*.branch_id' => 'nullable|exists:branches,id',
            'assignments.*.role_name' => 'nullable|string',
            'assignments.*.role' => 'nullable|string',
        ]);

        // Re-sync all branch-role assignments
        \DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->where('model_id', $user->id)
            ->delete();

        foreach ($request->assignments as $assignment) {
            $branchId = $assignment['branch_id'] ?? null;
            $roleName = $assignment['role_name'] ?? $assignment['role'] ?? null;
            if ($branchId && $roleName) {
                $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                if ($role) {
                    \DB::table('model_has_roles')->insertOrIgnore([
                        'role_id'    => $role->id,
                        'model_type' => 'App\\Models\\User',
                        'model_id'   => $user->id,
                        'branch_id'  => $branchId,
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Penugasan cabang dan jabatan pengguna berhasil disimpan.',
            'user' => $user->fresh(['roles']),
        ]);
    }
}
