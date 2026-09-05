<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'setting_key',
        'account_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get account ID by key helper
     */
    public static function getAccountId($key, $branchId = null)
    {
        $setting = static::where('setting_key', $key)
            ->where(function ($q) use ($branchId) {
                if ($branchId) {
                    $q->where('branch_id', $branchId)->orWhereNull('branch_id');
                } else {
                    $q->whereNull('branch_id');
                }
            })
            ->orderByRaw('branch_id IS NULL ASC') // specific branch first
            ->first();

        return $setting ? $setting->account_id : null;
    }
}
