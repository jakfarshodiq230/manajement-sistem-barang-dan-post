<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        // Spatie uses team_id in this project for branches, so we fetch roles globally or for the active team.
        // For simplicity, let's just fetch all roles and their permissions and users.
        $roles = Role::with(['permissions', 'users'])->get();

        $formattedRoles = $roles->map(function ($role) {
            
            // Map permissions to the Vue template structure
            $permissionsData = $role->permissions->map(function ($perm) {
                // We'll simplify the UI by parsing standard permissions if they follow the format "read module", "write module"
                // But the template expects { name: 'User Management', read: true, write: true, create: true }
                // So we'll just send the raw permissions and handle it on frontend, OR map it here.
                return [
                    'name' => $perm->name,
                    'read' => true,
                    'write' => true,
                    'create' => true,
                    'delete' => true,
                ];
            });

            // If no permissions, provide a default so UI doesn't break
            if ($permissionsData->isEmpty()) {
                $permissionsData = collect([['name' => 'All', 'read' => false, 'write' => false, 'create' => false]]);
            }

            return [
                'id' => $role->id,
                'role' => $role->name,
                'users' => $role->users->map(fn($u) => '')->take(5)->toArray(), // empty avatars for now
                'totalUsers' => $role->users->count(),
                'details' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $permissionsData,
                ]
            ];
        });

        return response()->json($formattedRoles);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->hasRole('Super Admin') && !$user->can('Roles Create') && !$user->can('create roles')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|unique:roles,name,NULL,id,guard_name,web',
            'permissions' => 'array'
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);
        
        if ($request->has('permissions')) {
            $permissionModels = collect($request->permissions)->map(function($perm) {
                $name = is_array($perm) ? ($perm['name'] ?? '') : $perm;
                if (!empty($name)) {
                    return Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
                }
                return null;
            })->filter();

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            $role->syncPermissions($permissionModels);
        }

        return response()->json(['message' => 'Peran berhasil dibuat', 'role' => $role], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if ($user && !$user->hasRole('Super Admin') && !$user->can('Roles Write') && !$user->can('write roles')) {
            abort(403, 'Unauthorized action.');
        }

        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id . ',id,guard_name,web',
            'permissions' => 'array'
        ]);

        $role->update(['name' => $request->name, 'guard_name' => 'web']);

        if ($request->has('permissions')) {
            $permissionModels = collect($request->permissions)->map(function($perm) {
                $name = is_array($perm) ? ($perm['name'] ?? '') : $perm;
                if (!empty($name)) {
                    return Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
                }
                return null;
            })->filter();

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            $role->syncPermissions($permissionModels);
        }

        return response()->json(['message' => 'Peran berhasil diperbarui', 'role' => $role]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user && !$user->hasRole('Super Admin') && !$user->can('Roles Delete') && !$user->can('delete roles')) {
            abort(403, 'Unauthorized action.');
        }

        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json(['message' => 'Peran berhasil dihapus']);
    }
}
