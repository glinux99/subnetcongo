<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Module extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_id',
        'uuid',
        'slug',
        'title',
        'description',
        'intro_video_url',
        'intro_video_title',
        'text_content',
        'objectives',
        'document_url',
        'document_name',
        'has_document',
        'document_title',
        'status',
        'order',
        'order_index',
        'order_in_course',
        'is_locked',
        'is_published',
        'estimated_duration',
        'thumbnail_path',
        'seo_metadata',
        'has_quiz',
        'quiz_id',
        'quiz_required',
        'quiz_weight',
        'quiz_passing_score',
        'quiz_attempts_allowed',
    ];

    protected $casts = [
        'order' => 'integer',
        'order_index' => 'integer',
        'order_in_course' => 'integer',
        'is_locked' => 'boolean',
        'is_published' => 'boolean',
        'estimated_duration' => 'integer',
        'seo_metadata' => 'array',
        'objectives' => 'array',
        'deleted_at' => 'datetime',
        'has_quiz' => 'boolean',
        'quiz_required' => 'boolean',
        'quiz_weight' => 'decimal:2',
        'quiz_passing_score' => 'decimal:2',
        'quiz_attempts_allowed' => 'integer',
        'has_document' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId)->orderBy('order');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function getTotalDurationAttribute()
    {
        return $this->duration_minutes + $this->lessons->sum('duration_minutes');
    }

    public function getUserProgress(User $user)
    {
        return $this->lessons->reduce(function ($carry, $lesson) use ($user) {
            $progress = $lesson->userProgress()->where('user_id', $user->id)->first();
            return $carry + ($progress ? $progress->progress_percent : 0);
        }, 0) / max($this->lessons->count(), 1);
    }
}
