<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StockTransfer extends Model
{
    use HasFactory, \Spatie\Activitylog\Traits\LogsActivity, \App\Traits\ScopedByBranch;

    public function getBranchScopeType(): string
    {
        return 'transfer_branches';
    }

    protected $fillable = [
        'uuid',
        'reference_no',
        'source_branch_id',
        'destination_branch_id',
        'status',
        'notes',
        'created_by',
        'prepared_by',
        'prepared_at',
        'approved_by',
        'received_by',
        'received_at',
        'picked_up_by_name',
        'picked_up_at',
        'pickup_courier_type',
        'pickup_photo',
        'received_photo',
        'receive_notes',
        'pickup_notes',
    ];

    protected $casts = [
        'prepared_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()->logAll();
    }

    public function sourceBranch()
    {
        return $this->belongsTo(Branch::class, 'source_branch_id');
    }

    public function destinationBranch()
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
