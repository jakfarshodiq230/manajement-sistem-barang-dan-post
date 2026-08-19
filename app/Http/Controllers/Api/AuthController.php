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

            // Load all branch-role assignments directly with leftJoin
            $assignments = DB::table('model_has_roles as mhr')
                ->join('roles', 'mhr.role_id', '=', 'roles.id')
                ->leftJoin('branches', 'mhr.branch_id', '=', 'branches.id')
                ->where('mhr.model_type', 'App\\Models\\User')
                ->where('mhr.model_id', $user->id)
                ->select(
                    'branches.id as branch_id',
                    DB::raw('COALESCE(branches.name, "Semua Cabang (Global)") as branch_name'),
                    'roles.id as role_id',
                    'roles.name as role_name'
                )
                ->get();

            $directRoles = $user->roles->pluck('name')->toArray();

            // Determine active role: prefer saved active_role_id, else first assignment or direct role
            $activeRoleName = null;
            if ($user->active_role_id) {
                $found = $assignments->firstWhere('role_id', $user->active_role_id);
                $activeRoleName = $found ? $found->role_name : null;
            }
            if (!$activeRoleName) {
                $firstAssig = $assignments->first();
                $activeRoleName = $firstAssig ? $firstAssig->role_name : (!empty($directRoles) ? $directRoles[0] : 'Super Admin');
            }

            // Build ability rules based on role
            $abilityRules = [];
            
            $isSuperAdmin = $user->hasRole('Super Admin') || $user->hasRole('Developer') || $user->hasRole('dev') 
                || in_array('Super Admin', $directRoles) || in_array('Developer', $directRoles)
                || $activeRoleName === 'Super Admin' || $activeRoleName === 'Developer' || $activeRoleName === 'dev'
                || $assignments->contains('role_name', 'Super Admin') || $assignments->contains('role_name', 'Developer');

            if ($isSuperAdmin) {
                $abilityRules[] = ['action' => 'manage', 'subject' => 'all'];
            } else {
                $allRoleNames = $assignments->pluck('role_name')->merge($directRoles)->unique()->filter();
                foreach ($allRoleNames as $rName) {
                    $role = \Spatie\Permission\Models\Role::where('name', $rName)->first();
                    if ($role) {
                        foreach ($role->permissions as $perm) {
                            $parts = explode(' ', $perm->name);
                            if (count($parts) >= 2) {
                                $action = strtolower(array_pop($parts));
                                $subject = implode(' ', $parts);
                                $abilityRules[] = ['action' => $action, 'subject' => $subject];
                            } else {
                                $abilityRules[] = ['action' => strtolower($perm->name), 'subject' => 'all'];
                            }
                        }
                    }
                }
            }    

            // Selalu berikan akses dasar (Auth) bagi setiap pengguna yang berhasil login
            $abilityRules[] = ['action' => 'read', 'subject' => 'Auth'];
            $abilityRules[] = ['action' => 'read', 'subject' => 'all'];

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

            $userAgent = request()->header('User-Agent');
            $os = 'Unknown OS';
            $browser = 'Unknown Browser';
            if (preg_match('/windows|win32/i', $userAgent)) $os = 'Windows';
            elseif (preg_match('/macintosh|mac os x/i', $userAgent)) $os = 'Mac';
            elseif (preg_match('/linux/i', $userAgent)) $os = 'Linux';
            elseif (preg_match('/android/i', $userAgent)) $os = 'Android';
            elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) $os = 'iOS';

            if (preg_match('/chrome|crios|crmo/i', $userAgent)) $browser = 'Chrome';
            elseif (preg_match('/firefox|fxios/i', $userAgent)) $browser = 'Firefox';
            elseif (preg_match('/safari/i', $userAgent)) $browser = 'Safari';
            elseif (preg_match('/opera|opr\//i', $userAgent)) $browser = 'Opera';
            elseif (preg_match('/edg/i', $userAgent)) $browser = 'Edge';

            $deviceName = $os . ' - ' . $browser;

            return response()->json([
                'userAbilityRules' => $abilityRules,
                'accessToken'      => $user->createToken($deviceName)->plainTextToken,
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
        $user->pos_pin = $request->pin;
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

        if ((string) $request->pin !== (string) $approver->pos_pin) {
            return response()->json(['message' => 'PIN Salah'], 400);
        }

        return response()->json(['message' => 'Otorisasi Berhasil', 'approver_id' => $approver->id]);
    }
}
