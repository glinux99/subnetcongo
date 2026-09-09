<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonCompletion extends Model
{
    protected $fillable = [
        'user_id',
        'lesson_id',
        'module_id',
        'course_id',
        'is_completed',
        'time_spent_seconds',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'time_spent_seconds' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function markCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    public function addTimeSpent(int $seconds): void
    {
        $this->increment('time_spent_seconds', $seconds);
    }

    public static function getOrCreateForUserAndLesson(int $userId, int $lessonId, int $moduleId, int $courseId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId, 'lesson_id' => $lessonId],
            ['module_id' => $moduleId, 'course_id' => $courseId]
        );
    }
}
