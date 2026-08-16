<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // For Teams feature, if you want users scoped by branch, you can filter them.
        // For Super Admin, they see all.
        $query = \App\Models\User::with(['branch', 'roles']);
        
        if (auth()->user()->branch_id && !auth()->user()->hasRole('Super Admin')) {
            $query->where('branch_id', auth()->user()->branch_id);
        }

        $users = $query->paginate(10);
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'branch_id' => 'nullable|exists:branches,id',
            'roles' => 'nullable|array'
        ]);

        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'branch_id' => $request->branch_id,
        ]);

        if ($request->has('roles')) {
            if ($user->branch_id) {
                setPermissionsTeamId($user->branch_id);
            } else {
                setPermissionsTeamId(null);
            }
            $user->assignRole($request->roles);
        }

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit($id)
    {
        $user = \App\Models\User::findOrFail($id);
        if ($user->branch_id) {
            setPermissionsTeamId($user->branch_id);
        } else {
            setPermissionsTeamId(null);
        }
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = \App\Models\User::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'branch_id' => 'nullable|exists:branches,id',
            'roles' => 'nullable|array'
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->branch_id = $request->branch_id;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        if ($request->has('roles')) {
            if ($user->branch_id) {
                setPermissionsTeamId($user->branch_id);
            } else {
                setPermissionsTeamId(null);
            }
            $user->syncRoles($request->roles);
        } else {
            if ($user->branch_id) {
                setPermissionsTeamId($user->branch_id);
            } else {
                setPermissionsTeamId(null);
            }
            $user->syncRoles([]);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        $user = \App\Models\User::findOrFail($id);
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
