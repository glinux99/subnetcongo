<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveSessionChatMessage extends Model
{
    protected $fillable = [
        'live_session_id',
        'user_id',
        'message',
        'user_name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('live_session_id', $sessionId)->orderByDesc('created_at')->limit(100);
    }
}
