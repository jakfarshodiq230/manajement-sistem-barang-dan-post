<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetBranchPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // For Super Admin, we might bypass or set a specific branch.
        if (auth()->check()) {
            $user = auth()->user();
            $branchId = $request->input('branch_id');

            if (!$branchId) {
                $branchId = $user->branch_id;
            }

            if (!$branchId) {
                // Try active_role_id first
                $query = \DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->where('model_type', get_class($user));
                    
                if ($user->active_role_id) {
                    $query->where('role_id', $user->active_role_id);
                }
                
                $assignment = $query->first();
                
                if ($assignment) {
                    $branchId = $assignment->branch_id;
                }
            }

            if ($branchId) {
                setPermissionsTeamId($branchId);
            }
        }

        return $next($request);
    }
}
