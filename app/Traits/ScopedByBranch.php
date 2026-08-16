<?php

namespace App\Traits;

use App\Scopes\BranchScope;

trait ScopedByBranch
{
    /**
     * Boot the trait and apply the global scope.
     *
     * @return void
     */
    protected static function bootScopedByBranch()
    {
        static::addGlobalScope(new BranchScope);
    }
}
