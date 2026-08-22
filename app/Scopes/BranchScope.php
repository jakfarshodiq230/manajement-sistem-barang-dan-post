<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class BranchScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // 1. Dynamic permission check: if user has wildcard or global manage permission
            if ($user->can('manage all') || $user->can('all') || $user->can('*')) {
                return;
            }

            // 2. Dynamic multi-branch assignments from model_has_roles
            $assignments = DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', get_class($user))
                ->pluck('branch_id');

            // If user has ANY assignment with branch_id = NULL (global access across all branches), bypass
            if ($assignments->contains(null) && $assignments->count() > 0) {
                return;
            }

            // Extract assigned branch IDs
            $assignedBranchIds = $assignments->filter()->unique()->values()->toArray();

            // If user explicitly switched to a specific active branch
            if ($user->branch_id && in_array($user->branch_id, $assignedBranchIds)) {
                $branchIds = [$user->branch_id];
            } else {
                $branchIds = $assignedBranchIds;
            }

            // Restrict query to the determined branches
            if (!empty($branchIds)) {
                $columnType = method_exists($model, 'getBranchScopeType') ? $model->getBranchScopeType() : 'branch_id';

                if ($columnType === 'branch_id') {
                    $builder->whereIn($model->getTable() . '.branch_id', $branchIds);
                } elseif ($columnType === 'transfer_branches') {
                    $table = $model->getTable();
                    $builder->where(function ($q) use ($table, $branchIds) {
                        $q->whereIn($table . '.source_branch_id', $branchIds)
                          ->orWhereIn($table . '.destination_branch_id', $branchIds);
                    });
                } elseif ($columnType === 'product_branch_id') {
                    $builder->whereHas('productBranch', function ($q) use ($branchIds) {
                        $q->whereIn('product_branches.branch_id', $branchIds);
                    });
                } elseif ($columnType === 'purchase_order_id') {
                    $builder->whereHas('purchaseOrder', function ($q) use ($branchIds) {
                        $q->whereIn('purchase_orders.branch_id', $branchIds);
                    });
                }
            }
        }
    }
}
