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
            
            // Allow Super Admin to bypass scope completely
            if ($user->hasRole('Super Admin')) {
                return;
            }

            // For all other users, restrict to branches they are assigned to
            $branchIds = DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', get_class($user))
                ->whereNotNull('branch_id')
                ->pluck('branch_id')
                ->toArray();

            $columnType = method_exists($model, 'getBranchScopeType') ? $model->getBranchScopeType() : 'branch_id';

            if ($columnType === 'branch_id') {
                $builder->whereIn($model->getTable() . '.branch_id', $branchIds);
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
