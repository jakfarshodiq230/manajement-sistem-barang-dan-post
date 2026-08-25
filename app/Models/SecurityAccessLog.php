<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityAccessLog extends Model
{
    use HasFactory;

    protected $table = 'security_access_logs';

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device_type',
        'operating_system',
        'browser',
        'event_type',
        'endpoint',
        'method',
        'status_code',
        'payload',
        'risk_level',
        'threat_tags',
        'is_blocked',
    ];

    protected $casts = [
        'threat_tags' => 'array',
        'is_blocked' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
