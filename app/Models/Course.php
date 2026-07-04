<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Course status constants.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    /**
     * Level constants.
     */
    public const LEVEL_BEGINNER = 'beginner';
    public const LEVEL_INTERMEDIATE = 'intermediate';
    public const LEVEL_ADVANCED = 'advanced';
    public const LEVEL_ALL = 'all';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'thumbnail_path',
        'price',
        'promo_price',
        'level',
        'status',
        'is_featured',
        'short_info',
        'tags',
        'seo_metadata',
        'instructor_id',
        'category_id',
        'created_by',
        'preview_video_url',
        'requires_enrollment',
        'certificate_enabled',
        'duration_minutes',
        'language',
        'prerequisites'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_featured' => 'boolean',
        'requires_enrollment' => 'boolean',
        'certificate_enabled' => 'boolean',
        'tags' => 'array',
        'seo_metadata' => 'array',
        'price' => 'decimal:2',
        'promo_price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the full URL for the thumbnail.
     */
    protected function thumbnailPath(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (empty($value)) {
                    return null;
                }
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    return $value;
                }
                return ($value);
            },
        );
    }

    /**
     * Get the instructor for the course.
     */
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Get the category for the course.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user who created the course.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the modules/sections for the course.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('order');
    }

    /**
     * Alias for sections() to maintain compatibility with DashboardController.
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('order');
    }

    /**
     * Get all lessons through modules.
     */
    public function lessons()
    {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

    /**
     * Get enrolled students.
     */
    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot('progress_percent', 'enrolled_at', 'completed_at')
            ->withTimestamps();
    }

    /**
     * Get enrollments for the course.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get orders for the course through order items.
     */
    public function orders()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get submissions for the course.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Get reviews for the course.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get exams for the course.
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Scope to get published courses.
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope to get featured courses.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get free courses.
     */
    public function scopeFree($query)
    {
        return $query->where('price', 0);
    }

    /**
     * Scope to get paid courses.
     */
    public function scopePaid($query)
    {
        return $query->where('price', '>', 0);
    }

    /**
     * Scope to get courses by level.
     */
    public function scopeOfLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope to get courses by category.
     */
    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to get courses by instructor.
     */
    public function scopeForInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    /**
     * Scope to search courses.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('slug', 'like', "%{$search}%")
              ->orWhere('short_info', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Check if course is published.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Check if course is free.
     */
    public function isFree(): bool
    {
        return $this->price == 0;
    }

    /**
     * Check if course has a promo price.
     */
    public function hasPromo(): bool
    {
        return $this->promo_price !== null && $this->promo_price < $this->price;
    }

    /**
     * Get the effective price (promo or regular).
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->hasPromo() ? $this->promo_price : $this->price;
    }

    /**
     * Get total duration including all lessons.
     */
    public function getTotalDurationAttribute(): int
    {
        return $this->lessons()->sum('duration_minutes');
    }

    /**
     * Get student count.
     */
    public function getStudentCountAttribute(): int
    {
        return $this->enrollments()->distinct('user_id')->count('user_id');
    }

    /**
     * Get average rating.
     */
    public function getAverageRatingAttribute(): float
    {
        return $this->reviews()->where('is_approved', true)->avg('rating') ?? 0;
    }

    /**
     * Get completion rate.
     */
    public function getCompletionRateAttribute(): float
    {
        return $this->enrollments()->avg('progress_percent') ?? 0;
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }
        return '$' . number_format($this->effective_price, 2);
    }

    /**
     * Get SEO title.
     */
    public function getSeoTitleAttribute(): string
    {
        return $this->seo_metadata['title'] ?? $this->title;
    }

    /**
     * Get SEO description.
     */
    public function getSeoDescriptionAttribute(): string
    {
        return $this->seo_metadata['description'] ?? $this->short_info ?? '';
    }

    /**
     * Get the preview video URL.
     */
    protected function previewVideoUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (empty($value)) {
                    return null;
                }
                // If it's a full URL (YouTube/Vimeo), return as is
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    return $value;
                }
                // If it's a local path, return the storage URL
                return Storage::disk('public')->url($value);
            },
        );
    }
}
