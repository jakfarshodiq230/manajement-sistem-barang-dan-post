<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q', '');
        $role = $request->query('role');
        $plan = $request->query('plan');
        $status = $request->query('status');
        $itemsPerPage = $request->query('itemsPerPage', 10);
        
        $query = User::query();

        if ($q) {
            $query->where(function($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $paginator = $query->paginate($itemsPerPage);

        $users = $paginator->map(function ($user) {
            // Load all branch-role assignments for this user from pivot table
            $assignments = \DB::table('model_has_roles as mhr')
                ->join('roles', 'mhr.role_id', '=', 'roles.id')
                ->join('branches', 'mhr.branch_id', '=', 'branches.id')
                ->where('mhr.model_type', 'App\\Models\\User')
                ->where('mhr.model_id', $user->id)
                ->select('branches.id as branch_id', 'branches.name as branch_name', 'roles.id as role_id', 'roles.name as role_name')
                ->get();

            return [
                'id' => $user->id,
                'fullName' => $user->name,
                'username' => strtolower(str_replace(' ', '', $user->name)),
                'email' => $user->email,
                'role' => $assignments->pluck('role_name')->unique()->values()->toArray(),
                'assignments' => $assignments->map(fn($a) => [
                    'branch_id'   => $a->branch_id,
                    'branch_name' => $a->branch_name,
                    'role_id'     => $a->role_id,
                    'role_name'   => $a->role_name,
                ])->values()->toArray(),
                'currentPlan' => 'basic',
                'status' => 'Active',
                'avatar' => '',
            ];
        });

        return response()->json([
            'users' => $users,
            'totalUsers' => $paginator->total(),
        ]);
    }

    public function store(Request $request)
    {
        if (!request()->user()->can('Pengguna Create')) {
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

        return response()->json([
            'id'           => $user->id,
            'fullName'     => $user->name,
            'username'     => strtolower(str_replace(' ', '', $user->name)),
            'email'        => $user->email,
            'role'         => $assignments->pluck('role_name')->unique()->values()->toArray(),
            'assignments'  => $assignments->map(fn($a) => [
                'branch_id'   => $a->branch_id,
                'branch_name' => $a->branch_name,
                'role_id'     => $a->role_id,
                'role_name'   => $a->role_name,
            ])->values()->toArray(),
            'currentPlan'  => 'basic',
            'status'       => 'Active',
            'avatar'       => '',
            'taskDone'     => 1230,
            'projectDone'  => 568,
            'taxId'        => 'Tax-8894',
            'language'     => 'English',
            'country'      => 'Indonesia',
            'contact'      => '+62 812-3456-7890',
        ]);
    }

    public function update(Request $request, User $user)
    {
        if (!request()->user()->can('Pengguna Write')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'fullName' => 'required',
            'email'    => 'required|email|unique:users,email,'.$user->id,
        ]);

        $user->update([
            'name'  => $request->fullName,
            'email' => $request->email,
        ]);

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

        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    public function destroy(User $user)
    {
        if (!request()->user()->can('Pengguna Delete')) {
            abort(403, 'Unauthorized action.');
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
