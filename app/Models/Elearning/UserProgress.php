<?php

namespace App\Models\Elearning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class UserProgress extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'lesson_id',
        'course_id',
        'progress_percent',
        'last_accessed_at',
        'completed_at',
        'time_spent_seconds',
        'notes',
        'last_accessed_at',
        'enrollment_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'progress_percent' => 'decimal:2',
        'time_spent_seconds' => 'integer',
        'last_accessed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the progress.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the lesson being tracked.
     */
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the course being tracked.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Scope to get progress for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get progress for a specific course.
     */
    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope to get completed progress records.
     */
    public function scopeCompleted($query)
    {
        return $query->where('progress_percent', 100)->whereNotNull('completed_at');
    }

    /**
     * Scope to get in-progress records.
     */
    public function scopeInProgress($query)
    {
        return $query->where('progress_percent', '>', 0)->where('progress_percent', '<', 100);
    }

    /**
     * Check if progress is completed.
     */
    public function isCompleted(): bool
    {
        return $this->progress_percent >= 100;
    }

    /**
     * Mark progress as complete.
     */
    public function markAsComplete()
    {
        $this->update([
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);
    }

    /**
     * Update progress percentage.
     */
    public function updateProgress(int $percent)
    {
        $data = [
            'progress_percent' => min(100, max(0, $percent)),
            'last_accessed_at' => now(),
            'time_spent_seconds' => $this->time_spent_seconds + 1,
        ];

        if ($percent >= 100) {
            $data['completed_at'] = now();
        }

        $this->update($data);
    }

    /**
     * Track time spent on lesson.
     */
    public function trackTime(int $seconds)
    {
        $this->increment('time_spent_seconds', $seconds);
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Get formatted time spent.
     */
    public function getFormattedTimeSpentAttribute(): string
    {
        $hours = floor($this->time_spent_seconds / 3600);
        $minutes = floor(($this->time_spent_seconds % 3600) / 60);
        $seconds = $this->time_spent_seconds % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }

        return "{$seconds}s";
    }
}
