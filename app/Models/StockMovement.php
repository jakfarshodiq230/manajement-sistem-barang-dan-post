<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity, \App\Traits\ScopedByBranch;

    public function getBranchScopeType()
    {
        return 'product_branch_id';
    }

    protected $fillable = [
        'product_branch_id',
        'user_id',
        'type',
        'quantity',
        'unit_cost',
        'reference_type',
        'reference_id',
        'notes',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }

    public function productBranch()
    {
        return $this->belongsTo(ProductBranch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
