<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const LEVEL_BEGINNER = 'beginner';
    public const LEVEL_INTERMEDIATE = 'intermediate';
    public const LEVEL_ADVANCED = 'advanced';
    public const LEVEL_ALL = 'all';

    protected $fillable = [
        'title', 'slug', 'description', 'thumbnail_path', 'price', 'promo_price',
        'level', 'status', 'is_featured', 'short_info', 'tags', 'seo_metadata',
        'instructor_id', 'category_id', 'created_by', 'preview_video_url',
        'requires_enrollment', 'certificate_enabled', 'duration_minutes',
        'language', 'prerequisites',
        'has_final_exam', 'final_exam_id', 'final_exam_weight',
        'passing_score_20', 'quiz_weight_total',
        'intro_video_url', 'intro_video_title', 'certificate_template_id',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'requires_enrollment' => 'boolean',
        'certificate_enabled' => 'boolean',
        'prerequisites' => 'array',
        'price' => 'decimal:2',
        'promo_price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'deleted_at' => 'datetime',
        'has_final_exam' => 'boolean',
        'final_exam_weight' => 'decimal:2',
        'passing_score_20' => 'decimal:2',
        'quiz_weight_total' => 'decimal:2',
    ];

    protected function tags(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? [],
        );
    }

    protected function seoMetadata(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? ['title' => '', 'description' => '', 'keywords' => '', 'og_image' => null],
        );
    }

    protected function prerequisites(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? [],
        );
    }

    /**
     * FIX: Correction de l'Accessor pour le thumbnail
     * La méthode doit correspondre au nom de la colonne en camelCase
     */
    protected function thumbnailPath(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (empty($value)) return null;
                if (filter_var($value, FILTER_VALIDATE_URL)) return $value;
                return Storage::disk('media')->url($value);
            },
        );
    }

    /**
     * RELATION CORRIGÉE : Payments
     * Indispensable pour DashboardController::getCohortsData
     */
    public function payments(): HasMany
    {
        // On suppose que vous avez un modèle Payment lié au Course
        return $this->hasMany(Payment::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('order');
    }

    // Alias pour la compatibilité
    public function sections(): HasMany
    {
        return $this->modules();
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot('progress_percent', 'enrolled_at', 'completed_at')
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
      public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
    
    /**
     * Si vos revenus passent par OrderItem au lieu de Payment directement
     */
    public function orders(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
/**
 * Récupère toutes les ressources (fichiers joints) associées à ce cours.
 * Relation Polymorphique : 1 Cours possède plusieurs Ressources.
 * 
 * @return \Illuminate\Database\Eloquent\Relations\MorphMany
 */
public function resources(): \Illuminate\Database\Eloquent\Relations\MorphMany
{
    return $this->morphMany(\App\Models\Resource::class, 'resourceable')
                ->orderBy('sort_order') // Tri par ordre défini dans l'éditeur
                ->orderBy('created_at', 'desc');
}
    // --- SCOPES ---

    public function scopePublished($query) { return $query->where('status', self::STATUS_PUBLISHED); }
    public function scopeFeatured($query) { return $query->where('is_featured', true); }
    public function scopeFree($query) { return $query->where('price', 0); }
    public function scopePaid($query) { return $query->where('price', '>', 0); }

    // --- ATTRIBUTS CALCULÉS (APPENDS) ---

    public function getEffectivePriceAttribute(): float
    {
        return ($this->promo_price && $this->promo_price < $this->price) ? $this->promo_price : $this->price;
    }

    public function getAverageRatingAttribute(): float
    {
        // On évite de crasher si la table reviews n'existe pas ou est vide
        return method_exists($this, 'reviews') ? ($this->reviews()->where('is_approved', true)->avg('rating') ?? 0) : 0;
    }

    /**
     * Accessor pour la vidéo de preview
     */
    protected function previewVideoUrl(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (empty($value)) return null;
                if (filter_var($value, FILTER_VALIDATE_URL)) return $value;
                return Storage::disk('media')->url($value);
            },
        );
    }
}
