<?php

namespace App\Models\Elearning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class ExamAttempt extends Model
{
    use HasFactory;

    /**
     * Attempt status constants.
     */
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REVIEW = 'review';
    public const STATUS_FLAGGED = 'flagged';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'exam_id',
        'started_at',
        'completed_at',
        'status',
        'answers',
        'raw_score',
        'ip_address',
        'user_agent',
        'time_spent_seconds',
        'proctoring_data',
        'flagged_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'answers' => 'array',
        'proctoring_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'raw_score' => 'decimal:2',
        'time_spent_seconds' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($attempt) {
            if (empty($attempt->uuid)) {
                $attempt->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the user who made the attempt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the exam for this attempt.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the result for this attempt.
     */
    public function result(): HasOne
    {
        return $this->hasOne(ExamResult::class);
    }

    /**
     * Scope to get attempts for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get attempts for a specific exam.
     */
    public function scopeForExam($query, $examId)
    {
        return $query->where('exam_id', $examId);
    }

    /**
     * Scope to get completed attempts.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get in-progress attempts.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope to get attempts within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('started_at', [$startDate, $endDate]);
    }

    /**
     * Check if attempt is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if attempt is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Mark attempt as complete.
     */
    public function markAsComplete()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get time spent formatted.
     */
    public function getFormattedTimeSpentAttribute(): string
    {
        if (!$this->time_spent_seconds) {
            return '0m';
        }

        $hours = floor($this->time_spent_seconds / 3600);
        $minutes = floor(($this->time_spent_seconds % 3600) / 60);
        $seconds = $this->time_spent_seconds % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m {$seconds}s";
    }

    /**
     * Calculate duration if completed.
     */
    public function getDurationMinutesAttribute(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return (int) $this->started_at->diffInMinutes($this->completed_at);
        }

        return null;
    }

    /**
     * Get answer count.
     */
    public function getAnswerCountAttribute(): int
    {
        return is_array($this->answers) ? count($this->answers) : 0;
    }
}
