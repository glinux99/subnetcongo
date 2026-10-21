<?php

namespace App\Models\Elearning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonQuestion extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_ANSWERED = 'answered';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'user_id', 'course_id', 'lesson_id', 'instructor_id', 'parent_id',
        'subject', 'body', 'attachments', 'status', 'priority', 'answer', 'answer_attachments', 'answered_at',
        'upvotes', 'is_public',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
        'is_public' => 'boolean',
        'upvotes' => 'integer',
        'attachments' => 'array',
        'answer_attachments' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(LessonQuestion::class, 'parent_id')->latest();
    }

    public function isAnswered(): bool
    {
        return in_array($this->status, [self::STATUS_ANSWERED, self::STATUS_RESOLVED], true);
    }

    /**
     * Liste des pièces jointes (toujours un array, jamais null côté front).
     */
    public function getAttachmentsAttribute($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    public function getAnswerAttachmentsAttribute($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    public function hasAttachments(): bool
    {
        return ! empty($this->attachments);
    }

    public function hasAnswerAttachments(): bool
    {
        return ! empty($this->answer_attachments);
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => 'rose',
            self::PRIORITY_HIGH => 'amber',
            self::PRIORITY_LOW => 'slate',
            default => 'indigo',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'amber',
            self::STATUS_ANSWERED => 'indigo',
            self::STATUS_RESOLVED => 'emerald',
            self::STATUS_CLOSED => 'slate',
            default => 'slate',
        };
    }
}
