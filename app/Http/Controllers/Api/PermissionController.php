<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q', '');
        $itemsPerPage = $request->query('itemsPerPage', 10);
        $sortBy = $request->query('sortBy');
        $orderBy = $request->query('orderBy');

        $query = Permission::with('roles');

        if ($q) {
            $query->where('name', 'like', "%{$q}%");
        }

        if ($sortBy) {
            $query->orderBy($sortBy, $orderBy === 'desc' ? 'desc' : 'asc');
        } else {
            $query->latest();
        }

        $paginator = $query->paginate($itemsPerPage);

        $permissions = $paginator->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'assignedTo' => $permission->roles->pluck('name')->toArray(),
                'createdDate' => $permission->created_at->format('d M Y, h:i A'),
            ];
        });

        return response()->json([
            'permissions' => $permissions,
            'totalPermissions' => $paginator->total(),
        ]);
    }

    public function store(Request $request)
    {
        if (!request()->user()->can('Permissions Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate(['name' => 'required|unique:permissions']);
        $permission = Permission::create(['name' => $request->name]);

        return response()->json(['message' => 'Permission created successfully', 'permission' => $permission], 201);
    }

    public function update(Request $request, $id)
    {
        if (!request()->user()->can('Permissions Write')) {
            abort(403, 'Unauthorized action.');
        }

        $permission = Permission::findOrFail($id);
        $request->validate(['name' => 'required|unique:permissions,name,' . $permission->id]);

        $permission->update(['name' => $request->name]);

        return response()->json(['message' => 'Permission updated successfully', 'permission' => $permission]);
    }

    public function destroy($id)
    {
        if (!request()->user()->can('Permissions Delete')) {
            abort(403, 'Unauthorized action.');
        }

        Permission::findOrFail($id)->delete();
        return response()->json(['message' => 'Permission deleted successfully']);
    }
}
