<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'live_session_id',
        'user_id',
        'joined_at',
        'left_at',
        'duration_minutes',
        'seconds_spent',
        'last_ping_at',
        'status',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'last_ping_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
