<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveSession extends Model
{
    /** Route URL sans ID (lien partageable) */
    public function getRouteKeyName()
    {
        return 'uuid';
    }
    protected $table = 'live_sessions';

    protected $fillable = [
        'course_id', 'instructor_id', 'cohort_id', 'created_by',
        'title',
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

    /**
     * La session est-elle ouverte au public (webinaire) ?
     */
    public function isPublicSession(): bool
    {
        return (($this->settings['visibility'] ?? 'private') === 'public');
    }

    /**
     * Liste normalisÃ©e des e-mails invitÃ©s.
     */
    public function invitedEmails(): array
    {
        $emails = (array) ($this->settings['invited_emails'] ?? []);

        return array_values(array_unique(array_filter(array_map(
            fn ($e) => strtolower(trim((string) $e)),
            $emails
        ))));
    }

    /**
     * Demandes d'accÃ¨s enregistrÃ©es (sans migration : stockÃ©es dans metadata).
     */
    public function joinRequests(): array
    {
        return array_values((array) ($this->metadata['join_requests'] ?? []));
    }

    public function hasApprovedAccess($userId): bool
    {
        foreach ($this->joinRequests() as $request) {
            if ((int) ($request['user_id'] ?? 0) === (int) $userId && ($request['status'] ?? null) === 'approved') {
                return true;
            }
        }

        return false;
    }

    public function hasPendingAccess($userId): bool
    {
        foreach ($this->joinRequests() as $request) {
            if ((int) ($request['user_id'] ?? 0) === (int) $userId && ($request['status'] ?? null) === 'pending') {
                return true;
            }
        }

        return false;
    }

    /**
     * Jeton opaque utilisÃ© dans les liens de partage (jamais l'ID en clair).
     */
    public function shareToken(): string
    {
        $settings = $this->settings ?? [];

        if (empty($settings['share_token'])) {
            $settings['share_token'] = \Illuminate\Support\Str::random(48);

            // Mise Ã  jour ciblÃ©e : la liste peut porter des attributs calculÃ©s
            // (pending_requests, share_url...) qu'il ne faut pas persister.
            static::query()->whereKey($this->getKey())->update([
                'settings' => json_encode($settings),
            ]);

            $this->setAttribute('settings', $settings);
            $this->syncOriginalAttribute('settings');
        }

        return $settings['share_token'];
    }

    public function shareUrl(): string
    {
        return route('live-sessions.shared', $this->shareToken());
    }
}
