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

    public const TYPE_VIDEO = 'video';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_QUIZ = 'quiz';
    public const TYPE_TEXT = 'text';
    public const TYPE_ASSIGNMENT = 'assignment';

    protected $fillable = [
        'module_id',
        'uuid',
        'title',
        'slug',
        'type',
        'lesson_type',
        'content',
        'content_text',
        'content_html',
        'video_url',
        'video_source',
        'video_duration',
        'duration_minutes',
        'order',
        'is_free_preview',
        'is_published',
        'is_sequential',
        'attachment_paths',
        'has_attachment',
        'attachment_title',
        'document_title',
        'document_url',
        'document_name',
        'quiz_id',
        'seo_metadata',
    ];

    protected $casts = [
        'order' => 'integer',
        'duration_minutes' => 'integer',
        'video_duration' => 'integer',
        'is_free_preview' => 'boolean',
        'is_published' => 'boolean',
        'is_sequential' => 'boolean',
        'attachment_paths' => 'array',
        'seo_metadata' => 'array',
        'has_attachment' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id')
            ->through('module');
    }

    public function userProgress()
    {
        return $this->hasMany(UserProgress::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Exam::class, 'quiz_id');
    }

    public function scopeForModule($query, $moduleId)
    {
        return $query->where('module_id', $moduleId)->orderBy('order');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFreePreview($query)
    {
        return $query->where('is_free_preview', true);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

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

    public function isVideo(): bool
    {
        return $this->type === self::TYPE_VIDEO;
    }

    public function isQuiz(): bool
    {
        return $this->type === self::TYPE_QUIZ;
    }

    public function isDocument(): bool
    {
        return $this->type === self::TYPE_DOCUMENT;
    }

    public function isText(): bool
    {
        return $this->type === self::TYPE_TEXT;
    }

    public function getCompletionRateAttribute()
    {
        $progressRecords = $this->userProgress;
        if ($progressRecords->isEmpty()) {
            return 0;
        }
        $completed = $progressRecords->where('progress_percent', 100)->count();
        return round(($completed / $progressRecords->count()) * 100, 1);
    }

    public function getAverageProgressAttribute()
    {
        return $this->userProgress->avg('progress_percent') ?? 0;
    }
}
