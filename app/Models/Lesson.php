<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Lesson types.
     */
    public const TYPE_VIDEO = 'video';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_QUIZ = 'quiz';
    public const TYPE_TEXT = 'text';
    public const TYPE_ASSIGNMENT = 'assignment';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'module_id',
        'uuid',
        'title',
        'slug',
        'type',
        'content',
        'video_url',
        'duration_minutes',
        'order',
        'is_free_preview',
        'is_published',
        'attachment_paths',
        'quiz_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'order' => 'integer',
        'duration_minutes' => 'integer',
        'is_free_preview' => 'boolean',
        'is_published' => 'boolean',
        'attachment_paths' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the module that owns the lesson.
     */
    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the course through the module.
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id')
            ->through('module');
    }

    /**
     * Get user progress for this lesson.
     */
    public function userProgress()
    {
        return $this->hasMany(UserProgress::class);
    }

    /**
     * Get the quiz associated with this lesson.
     */
    public function quiz()
    {
        return $this->belongsTo(Exam::class, 'quiz_id');
    }

    /**
     * Scope to get lessons for a specific module in order.
     */
    public function scopeForModule($query, $moduleId)
    {
        return $query->where('module_id', $moduleId)->orderBy('order');
    }

    /**
     * Scope to get published lessons.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to get free preview lessons.
     */
    public function scopeFreePreview($query)
    {
        return $query->where('is_free_preview', true);
    }

    /**
     * Scope to get lessons by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the URL for the attachment.
     */
    protected function attachmentPath(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (empty($value)) {
                    return null;
                }
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    return $value;
                }
                return Storage::disk('public')->url($value);
            },
        );
    }

    /**
     * Check if lesson is a video type.
     */
    public function isVideo(): bool
    {
        return $this->type === self::TYPE_VIDEO;
    }

    /**
     * Check if lesson is a quiz type.
     */
    public function isQuiz(): bool
    {
        return $this->type === self::TYPE_QUIZ;
    }

    /**
     * Check if lesson is a document type.
     */
    public function isDocument(): bool
    {
        return $this->type === self::TYPE_DOCUMENT;
    }

    /**
     * Get completion rate for this lesson.
     */
    public function getCompletionRateAttribute()
    {
        $progressRecords = $this->userProgress;
        if ($progressRecords->isEmpty()) {
            return 0;
        }
        $completed = $progressRecords->where('progress_percent', 100)->count();
        return round(($completed / $progressRecords->count()) * 100, 1);
    }

    /**
     * Get average progress for this lesson.
     */
    public function getAverageProgressAttribute()
    {
        return $this->userProgress->avg('progress_percent') ?? 0;
    }
}
