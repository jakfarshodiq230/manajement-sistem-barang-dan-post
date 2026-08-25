<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SystemAlertNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Send notification to all users assigned to a specific branch
     * Optionally filter by specific roles within that branch
     */
    public static function notifyBranch(
        int $branchId,
        string $title,
        string $message,
        ?string $url = null,
        string $type = 'info',
        string $icon = 'ri-notification-3-line',
        ?array $roles = null
    ): void {
        try {
            // Find users having direct branch_id or assigned in model_has_roles for this branch
            $userIdsQuery = DB::table('users')
                ->where('branch_id', $branchId)
                ->pluck('id');

            $assignedUserIds = DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id'); // Global users
                });

            if (!empty($roles)) {
                $roleIds = DB::table('roles')->whereIn('name', $roles)->pluck('id');
                $assignedUserIds->whereIn('role_id', $roleIds);
            }

            $mhrUserIds = $assignedUserIds->pluck('model_id');
            $allTargetUserIds = $userIdsQuery->merge($mhrUserIds)->unique()->filter();

            $users = User::whereIn('id', $allTargetUserIds)->get();

            if ($users->isNotEmpty()) {
                Notification::send($users, new SystemAlertNotification($title, $message, $url, $type, $icon, 'branch_alert', $branchId));
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to send branch notification: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to specific roles globally across the organization (e.g. Owner, Super Admin)
     */
    public static function notifyRoles(
        array $roles,
        string $title,
        string $message,
        ?string $url = null,
        string $type = 'primary',
        string $icon = 'ri-notification-3-line',
        ?int $branchId = null
    ): void {
        try {
            $roleIds = DB::table('roles')->whereIn('name', $roles)->pluck('id');

            $userIdsFromMhr = DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->whereIn('role_id', $roleIds)
                ->pluck('model_id');

            $activeRoleUsers = DB::table('users')
                ->whereIn('active_role_id', $roleIds)
                ->pluck('id');

            $allTargetUserIds = $userIdsFromMhr->merge($activeRoleUsers)->unique()->filter();
            $users = User::whereIn('id', $allTargetUserIds)->get();

            if ($users->isNotEmpty()) {
                Notification::send($users, new SystemAlertNotification($title, $message, $url, $type, $icon, 'role_alert', $branchId));
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to send role notification: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to all users who possess specific permission(s) from database RBAC
     */
    public static function notifyPermission(
        string|array $permissions,
        string $title,
        string $message,
        ?string $url = null,
        string $type = 'primary',
        string $icon = 'ri-notification-3-line',
        ?int $branchId = null
    ): void {
        try {
            $perms = is_array($permissions) ? $permissions : [$permissions];
            $users = User::all()->filter(function ($u) use ($perms) {
                foreach ($perms as $p) {
                    if ($u->can($p)) return true;
                }
                return false;
            });

            if ($users->isNotEmpty()) {
                Notification::send($users, new SystemAlertNotification($title, $message, $url, $type, $icon, 'permission_alert', $branchId));
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to send permission notification: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to Owner, Executives and Global Admins via database RBAC permissions
     */
    public static function notifyOwnerAndAdmins(
        string $title,
        string $message,
        ?string $url = null,
        string $type = 'warning',
        string $icon = 'ri-alarm-warning-line',
        ?int $branchId = null
    ): void {
        self::notifyPermission(['manage all', 'Modal & ROI Cabang Approve', 'Dashboard Keuntungan Read', 'Audit & Laporan Approve'], $title, $message, $url, $type, $icon, $branchId);
    }

    /**
     * Send notification to a specific single user
     */
    public static function notifyUser(
        int $userId,
        string $title,
        string $message,
        ?string $url = null,
        string $type = 'primary',
        string $icon = 'ri-notification-3-line',
        ?int $branchId = null
    ): void {
        try {
            $user = User::find($userId);
            if ($user) {
                $user->notify(new SystemAlertNotification($title, $message, $url, $type, $icon, 'user_alert', $branchId));
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to send user notification: ' . $e->getMessage());
        }
    }
}
