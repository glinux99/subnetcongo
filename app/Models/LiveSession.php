<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveSession extends Model
{
    protected $table = 'live_sessions';

    protected $fillable = [
        'course_id', 'instructor_id', 'cohort_id', 'created_by',
        'title', 'slug',
        'start_at', 'actual_start_at', 'ended_at', 'duration',
        'provider', 'max_participants', 'is_recorded', 'passcode',
        'settings', 'metadata', 'provider_response',
        'meeting_id', 'meeting_url', 'host_url',
        'status',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_recorded' => 'boolean',
        'settings' => 'array',
        'metadata' => 'array',
        'provider_response' => 'array',
    ];

    public const PROVIDER_ZOOM = 'zoom';
    public const PROVIDER_MEET = 'meet';
    public const PROVIDER_BBB = 'bbb';
    public const PROVIDER_JITSI = 'jitsi';
    public const PROVIDER_LIVEKIT = 'livekit';

    public const STATUS_SCHEDULED = 'Scheduled';
    public const STATUS_LIVE = 'Live';
    public const STATUS_ENDED = 'Ended';
    public const STATUS_CANCELLED = 'Cancelled';

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'live_session_user')
            ->withPivot('joined_at', 'left_at')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(LiveAttendance::class, 'session_id');
    }

    public function recordings()
    {
        return $this->hasMany(Recording::class, 'live_session_id');
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    public function isUpcoming(): bool
    {
        return in_array($this->status, [self::STATUS_SCHEDULED], true) && $this->start_at?->isFuture();
    }

    public function isPast(): bool
    {
        return $this->status === self::STATUS_ENDED || $this->ended_at?->isPast();
    }
}
