<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasFactory;

    protected $table = 'email_logs';

    protected $fillable = [
        'recipient_email',
        'recipient_name',
        'subject',
        'email_type',
        'trigger_mode',
        'reference_type',
        'reference_id',
        'status',
        'sent_at',
        'error_message',
        'user_id',
        'branch_id',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForReference($query, string $type, $id)
    {
        return $query->where('reference_type', $type)->where('reference_id', (string) $id);
    }
}
