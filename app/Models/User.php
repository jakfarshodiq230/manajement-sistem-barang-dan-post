<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'pin',
        'active_role_id',
        'branch_id',
        'phone',
        'address',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    public function getNipAttribute()
    {
        if ($this->employee && !empty($this->employee->nik)) {
            return $this->employee->nik;
        }
        return 'EMP-' . str_pad($this->id, 3, '0', STR_PAD_LEFT);
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        $permissionName = is_string($permission) ? $permission : ($permission->name ?? '');

        // 1. Direct role check or manage all
        $roleIds = \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->where('model_id', $this->id)
            ->where('model_type', get_class($this))
            ->pluck('role_id')
            ->unique();

        if ($roleIds->isNotEmpty()) {
            $hasPerm = \Illuminate\Support\Facades\DB::table('role_has_permissions')
                ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
                ->whereIn('role_has_permissions.role_id', $roleIds)
                ->where(function ($q) use ($permissionName) {
                    $q->where('permissions.name', $permissionName)
                      ->orWhere('permissions.name', 'manage all')
                      ->orWhere('permissions.name', 'all')
                      ->orWhere('permissions.name', '*');
                })
                ->exists();

            if ($hasPerm) {
                return true;
            }
        }

        try {
            return $this->hasDirectPermission($permission);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
