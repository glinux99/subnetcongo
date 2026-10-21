<?php

namespace App\Models\Elearning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Billing\Order;

class Enrollment extends Model
{
    use HasFactory;

    /**
     * Enrollment statuses.
     * pending  : en attente d'approbation admin (cas d'un cours payant sans paiement)
     * active   : étudiant inscrit, peut suivre
     * suspended: inscription suspendue par l'admin (révocable, réactivable)
     * completed: cours terminé
     * cancelled: annulée par l'étudiant
     * rejected : refusée par l'admin (cours non payé, pas autorisé, etc.)
     * expired  : expiration naturelle (date dépassée)
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'course_id',
        'order_id',
        'progress_percent',
        'avg_score',
        'status',
        'enrolled_at',
        'completed_at',
        'expires_at',
        'certificate_id',
        'grade',
        'notes',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
        'revoked_at',
        'revoked_reason',
        'last_lesson_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'progress_percent' => 'decimal:2',
        'avg_score' => 'decimal:2',
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Get the user that owns the enrollment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course associated with the enrollment.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function lastLesson()
    {
        return $this->belongsTo(Lesson::class, 'last_lesson_id');
    }

    /**
     * Get the certificate for this enrollment.
     */
    public function certificate()
    {
        return $this->belongsTo(Certificate::class);
    }

    /**
     * Get user progress records for this enrollment.
     */
    public function progress()
    {
        return $this->hasMany(UserProgress::class);
    }

    /**
     * Scope to get enrollments for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get enrollments for a specific course.
     */
    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope to get active enrollments.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get completed enrollments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get enrollments with progress > 0.
     */
    public function scopeInProgress($query)
    {
        return $query->where('progress_percent', '>', 0)
                     ->where('progress_percent', '<', 100)
                     ->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', self::STATUS_SUSPENDED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeAccessible($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_COMPLETED]);
    }

    /**
     * Scope to get enrollments that are not completed.
     */
    public function scopeNotCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_ACTIVE]);
    }

    /**
     * Check if enrollment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED || $this->progress_percent >= 100;
    }

    /**
     * Check if enrollment is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if enrollment is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Mark enrollment as complete.
     */
    public function markAsComplete()
    {
        $this->update([
            'progress_percent' => 100,
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Statut lisible (FR).
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_ACTIVE => 'Validée',
            self::STATUS_SUSPENDED => 'Suspendue',
            self::STATUS_COMPLETED => 'Terminée',
            self::STATUS_CANCELLED => 'Annulée',
            self::STATUS_REJECTED => 'Refusée',
            self::STATUS_EXPIRED => 'Expirée',
            default => $this->status ?? '—',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'amber',
            self::STATUS_ACTIVE => 'emerald',
            self::STATUS_SUSPENDED => 'rose',
            self::STATUS_COMPLETED => 'indigo',
            self::STATUS_CANCELLED => 'slate',
            self::STATUS_REJECTED => 'rose',
            self::STATUS_EXPIRED => 'slate',
            default => 'slate',
        };
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isAccessible(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_COMPLETED], true);
    }

    /**
     * Update progress for this enrollment.
     */
    public function updateProgress(int $percent)
    {
        $data = [
            'progress_percent' => min(100, max(0, $percent)),
        ];

        if ($percent >= 100) {
            $data['status'] = self::STATUS_COMPLETED;
            $data['completed_at'] = now();
        }

        $this->update($data);
    }

    /**
     * Get completion percentage formatted.
     */
    public function getFormattedProgressAttribute(): string
    {
        return number_format($this->progress_percent, 1) . '%';
    }

    /**
     * Get days remaining until expiration.
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->expires_at));
    }

    /**
     * Calculate global success rate.
     */
    public static function calculateGlobalSuccessRate(): float
    {
        $total = self::count();
        if ($total === 0) {
            return 0;
        }
        $completed = self::where('status', self::STATUS_COMPLETED)
                        ->orWhere('progress_percent', 100)
                        ->count();
        return round(($completed / $total) * 100, 1);
    }

    /**
     * Get enrollment statistics.
     */
    public static function getStatistics()
    {
        return [
            'total' => self::count(),
            'active' => self::where('status', self::STATUS_ACTIVE)->count(),
            'completed' => self::where('status', self::STATUS_COMPLETED)->count(),
            'in_progress' => self::whereIn('status', [self::STATUS_PENDING, self::STATUS_ACTIVE])
                ->where('progress_percent', '>', 0)
                ->where('progress_percent', '<', 100)
                ->count(),
            'avg_progress' => round(self::avg('progress_percent') ?? 0, 1),
            'completion_rate' => self::calculateGlobalSuccessRate(),
        ];
    }
}
