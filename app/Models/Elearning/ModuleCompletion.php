<?php

namespace App\Models\Elearning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleCompletion extends Model
{
    protected $fillable = [
        'user_id',
        'module_id',
        'course_id',
        'intro_video_completed',
        'lessons_completed',
        'quiz_completed',
        'document_downloaded',
        'is_completed',
        'quiz_score',
        'completed_at',
    ];

    protected $casts = [
        'intro_video_completed' => 'boolean',
        'lessons_completed' => 'boolean',
        'quiz_completed' => 'boolean',
        'document_downloaded' => 'boolean',
        'is_completed' => 'boolean',
        'quiz_score' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function markIntroVideoCompleted(): void
    {
        $this->update(['intro_video_completed' => true]);
        $this->checkCompletion();
    }

    public function markLessonsCompleted(): void
    {
        $this->update(['lessons_completed' => true]);
        $this->checkCompletion();
    }

    public function markQuizCompleted(float $score): void
    {
        $this->update([
            'quiz_completed' => true,
            'quiz_score' => $score,
        ]);
        $this->checkCompletion();
    }

    public function markDocumentDownloaded(): void
    {
        $this->update(['document_downloaded' => true]);
    }

    public function checkCompletion(): void
    {
        $module = $this->module;
        
        $canComplete = true;
        
        if ($module->intro_video_url && !$this->intro_video_completed) {
            $canComplete = false;
        }
        
        if (!$this->lessons_completed) {
            $canComplete = false;
        }
        
        if ($module->has_quiz && $module->quiz_required && !$this->quiz_completed) {
            $canComplete = false;
        }
        
        if ($canComplete) {
            $this->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);
        }
    }

    public static function getOrCreateForUserAndModule(int $userId, int $moduleId, int $courseId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId, 'module_id' => $moduleId],
            ['course_id' => $courseId]
        );
    }
}
