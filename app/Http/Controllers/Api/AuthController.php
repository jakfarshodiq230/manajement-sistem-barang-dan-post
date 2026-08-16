<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();

            // Load all branch-role assignments directly (bypass Spatie team scope)
            $assignments = DB::table('model_has_roles as mhr')
                ->join('roles', 'mhr.role_id', '=', 'roles.id')
                ->join('branches', 'mhr.branch_id', '=', 'branches.id')
                ->where('mhr.model_type', 'App\\Models\\User')
                ->where('mhr.model_id', $user->id)
                ->select('branches.id as branch_id', 'branches.name as branch_name', 'roles.id as role_id', 'roles.name as role_name')
                ->get();

            // Determine active role: prefer saved active_role_id, else first assignment
            $activeRoleName = null;
            if ($user->active_role_id) {
                $found = $assignments->firstWhere('role_id', $user->active_role_id);
                $firstAssig = $assignments->first();
                $activeRoleName = $found ? $found->role_name : ($firstAssig ? $firstAssig->role_name : null);
            } else {
                $firstAssig = $assignments->first();
                $activeRoleName = $firstAssig ? $firstAssig->role_name : null;
            }

            // Build ability rules based on role
            $abilityRules = [];
            
            if ($activeRoleName) {
                $role = \Spatie\Permission\Models\Role::findByName($activeRoleName);
                if ($role) {
                    $permissions = $role->permissions->pluck('name');
                    foreach ($permissions as $perm) {
                        $parts = explode(' ', $perm);
                        if (count($parts) >= 2) {
                            $action = strtolower(array_pop($parts));
                            $subject = implode(' ', $parts);
                            $abilityRules[] = ['action' => $action, 'subject' => $subject];
                        } else {
                            $abilityRules[] = ['action' => strtolower($perm), 'subject' => 'all'];
                        }
                    }
                }
            }    

            // Selalu berikan akses dasar (Auth) bagi setiap pengguna yang berhasil login
            $abilityRules[] = ['action' => 'read', 'subject' => 'Auth'];

            $userData = [
                'id'          => $user->id,
                'fullName'    => $user->name,
                'username'    => strtolower(str_replace(' ', '', $user->name)),
                'avatar'      => '',
                'email'       => $user->email,
                'role'        => $activeRoleName,
                'assignments' => $assignments->map(fn($a) => [
                    'branch_id'   => $a->branch_id,
                    'branch_name' => $a->branch_name,
                    'role_id'     => $a->role_id,
                    'role_name'   => $a->role_name,
                ])->values()->toArray(),
            ];

            return response()->json([
                'userAbilityRules' => $abilityRules,
                'accessToken'      => $user->createToken('auth_token')->plainTextToken,
                'userData'         => $userData,
            ], 201);
        }

        return response()->json([
            'errors' => ['email' => ['Invalid email or password']],
        ], 400);
    }

    public function updatePin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|digits:6',
        ]);

        $user = $request->user();
        $user->pos_pin = \Illuminate\Support\Facades\Hash::make($request->pin);
        $user->save();

        return response()->json(['message' => 'PIN berhasil diperbarui']);
    }

    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string',
        ]);

        $approver = $request->user();

        if (!$approver->pos_pin) {
            return response()->json(['message' => 'Otorisator belum mengatur PIN mereka. Silakan atur di pengaturan.'], 400);
        }

        if (!\Illuminate\Support\Facades\Hash::check($request->pin, $approver->pos_pin)) {
            return response()->json(['message' => 'PIN Salah'], 400);
        }

        return response()->json(['message' => 'Otorisasi Berhasil', 'approver_id' => $approver->id]);
    }
}
