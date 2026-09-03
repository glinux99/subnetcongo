<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;


use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Region;
use App\Models\Zone;
use Spatie\MediaLibrary\HasMedia;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\InteractsWithMedia;
class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles,InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
         'provider_name',
        'provider_id',
        'fonction',
        'numero',
        'profile_photo', // This field is likely for a direct path, consider using Spatie Media Library for file uploads
        'hourly_rate', // Added for DashboardController's labor cost calculation
        'status',
        'phone',
        'cohort_id',
        'country',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Expose Spatie Media avatar URL as profile_photo_url in arrays/JSON
    // (pas dans $appends : on évite que SQL tente de sélectionner une colonne inexistante.
    //  L'accesseur getProfilePhotoUrlAttribute() reste utilisable via ->profile_photo_url)
 public function logins(): HasMany
    {
        return $this->hasMany(Login::class);
    }

    /**
     * Get the sessions for the user (Laravel database session driver).
     */
    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function getCreatedAtAttribute()
    {
        return date('d-m-Y H:i', strtotime($this->attributes['created_at']));
    }

    public function getUpdatedAtAttribute()
    {
        return date('d-m-Y H:i', strtotime($this->attributes['updated_at']));
    }

    public function getEmailVerifiedAtAttribute()
    {
        return $this->attributes['email_verified_at'] == null ? null:date('d-m-Y H:i', strtotime($this->attributes['email_verified_at']));
    }

    public function getPermissionArray()
    {
        return $this->getAllPermissions()->mapWithKeys(function ($pr) {
            return [$pr['name'] => true];
        });
    }
        public function region()
{
    return $this->belongsTo(Region::class);
}

 public function zone()
{
 return $this->belongsTo(Zone::class);
}

// Dans app/Models/User.php

/**
 * Les équipes auxquelles l'utilisateur appartient.
 */

public function teams()
{
    return $this->belongsToMany(Team::class);
}

/**
 * Get the chat groups the user belongs to.
 */
public function chatGroups()
{
    return $this->belongsToMany(ChatGroup::class, 'chat_group_user', 'user_id', 'chat_group_id')->withTimestamps();
}

/**
 * Get the extended profile for the user.
 */
public function profile()
{
    return $this->hasOne(Profile::class);
}

/**
 * Accessor: profile_photo_url from DB column or Spatie Media.
 */
public function getProfilePhotoUrlAttribute(): ?string
{
    // 1. Colonne profile_photo (chemin storage) ou avatar (URL)
    if (! empty($this->attributes['profile_photo'])) {
        return \Illuminate\Support\Facades\Storage::url($this->attributes['profile_photo']);
    }
    if (! empty($this->attributes['avatar'])) {
        return $this->attributes['avatar'];
    }
    // 2. Spatie Media (collection 'avatar')
    if (method_exists($this, 'getLastMediaUrl')) {
        try {
            $url = $this->getLastMediaUrl('avatar');
            if ($url) return $url;
        } catch (\Throwable $e) {
            // ignore
        }
    }
    return null;
}

/**
 * Get the activities assigned to the user.
 */
public function activities(): HasMany
{
    return $this->hasMany(Activity::class, 'user_id');
}

/**
 * Get the settings for the user.
 * Note: This assumes a 'user_id' foreign key exists on the 'settings' table.
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
public function settings(): HasMany
{
    return $this->hasMany(Setting::class);
}

// ===================================================================
// E-LEARNING RELATIONSHIPS
// ===================================================================

/**
 * Get all cohorts the user belongs to.
 */
public function cohorts()
{
    return $this->belongsToMany(Cohort::class, 'cohort_user', 'user_id', 'cohort_id')->withTimestamps();
}

/**
 * Get the latest cohort the user belongs to.
 */
public function cohort(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(Cohort::class);
}

/**
 * Get courses taught by this user (as instructor).
 */
public function taughtCourses(): HasMany
{
    return $this->hasMany(Course::class, 'instructor_id');
}

    /**
     * Alias for enrolledCourses or taughtCourses depending on context.
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

/**
 * Get enrollments for this user (as student).
 */
public function enrollments(): HasMany
{
    return $this->hasMany(Enrollment::class);
}

/**
 * Get courses enrolled by this user.
 */
public function enrolledCourses()
{
    return $this->belongsToMany(Course::class, 'enrollments')
        ->withPivot('progress_percent', 'enrolled_at', 'completed_at')
        ->withTimestamps();
}

/**
 * Get learning paths the user is enrolled in.
 */
public function learningPaths()
{
    return $this->belongsToMany(LearningPath::class, 'learning_path_user')
        ->withPivot('progress', 'enrolled_at', 'completed_at')
        ->withTimestamps();
}

/**
 * Get progress records for this user.
 */
public function progress(): HasMany
{
    return $this->hasMany(UserProgress::class);
}

/**
 * Get exam attempts for this user.
 */
public function examAttempts(): HasMany
{
    return $this->hasMany(ExamAttempt::class);
}

/**
 * Get exam results for this user.
 */
public function examResults(): HasMany
{
    return $this->hasMany(ExamResult::class);
}

/**
 * Get certificates earned by this user.
 */
public function certificates(): HasMany
{
    return $this->hasMany(Certificate::class);
}

/**
 * Get reviews written by this user.
 */
public function reviews(): HasMany
{
    return $this->hasMany(Review::class);
}

/**
 * Get assignment submissions for this user.
 */
public function submissions(): HasMany
{
    return $this->hasMany(Submission::class);
}

public function tickets(): HasMany
{
    return $this->hasMany(SupportTicket::class, 'user_id');
}

public function orders(): HasMany
{
    return $this->hasMany(Order::class, 'user_id');
}

public function cartItems(): HasMany
{
    return $this->hasMany(CartItem::class, 'user_id');
}

public function wishlist(): HasMany
{
    return $this->hasMany(Wishlist::class, 'user_id');
}

// ===================================================================
// E-LEARNING SCOPES
// ===================================================================

/**
 * Scope to get users who are students.
 */
public function scopeStudents($query)
{
    return $query->role('student');
}

/**
 * Scope to get users who are instructors.
 */
public function scopeInstructors($query)
{
    return $query->role('instructor');
}

/**
 * Scope to get users who are both instructors and admins.
 */
public function scopeAdmins($query)
{
    return $query->role('admin');
}

// ===================================================================
// E-LEARNING METHODS
// ===================================================================

/**
 * Check if user is a student.
 */
public function isStudent(): bool
{
    return $this->hasRole('student');
}

/**
 * Check if user is an instructor.
 */
public function isInstructor(): bool
{
    return $this->hasRole('instructor');
}

/**
 * Check if user is an admin.
 */
public function isAdmin(): bool
{
    return $this->hasRole('admin');
}

/**
 * Enroll user in a course. Default status = ACTIVE.
 * Pour un cours payant sans paiement validé, passer STATUS_PENDING.
 */
public function enrollIn(Course $course, array $pivotData = [], string $status = Enrollment::STATUS_ACTIVE): Enrollment
{
    $enrollment = $this->enrollments()->firstOrCreate(
        ['course_id' => $course->id],
        array_merge([
            'status' => $status,
            'progress_percent' => 0,
            'enrolled_at' => $status === Enrollment::STATUS_ACTIVE ? now() : null,
            'reviewed_at' => $status === Enrollment::STATUS_ACTIVE ? now() : null,
            'reviewed_by' => $status === Enrollment::STATUS_ACTIVE ? $this->id : null,
        ], $pivotData)
    );

    return $enrollment;
}

/**
 * Demande d'inscription (payante ou gratuite avec validation).
 */
public function requestEnrollment(Course $course, array $pivotData = []): Enrollment
{
    return $this->enrollIn($course, $pivotData, Enrollment::STATUS_PENDING);
}

/**
 * Vérifier si l'utilisateur a déjà une demande d'inscription en attente.
 */
public function hasPendingEnrollment(Course $course): bool
{
    return $this->enrollments()
        ->where('course_id', $course->id)
        ->where('status', Enrollment::STATUS_PENDING)
        ->exists();
}

/**
 * Vérifier si l'utilisateur a déjà été rejeté pour ce cours.
 */
public function wasRejectedFor(Course $course): bool
{
    return $this->enrollments()
        ->where('course_id', $course->id)
        ->where('status', Enrollment::STATUS_REJECTED)
        ->exists();
}

/**
 * Check if user is enrolled in a course.
 */
public function isEnrolledIn(Course $course): bool
{
    return $this->enrollments()
        ->where('course_id', $course->id)
        ->whereIn('status', [Enrollment::STATUS_ACTIVE, Enrollment::STATUS_COMPLETED])
        ->exists();
}

/**
 * Get progress for a specific course.
 */
public function getProgressFor(Course $course): ?Enrollment
{
    return $this->enrollments()
        ->where('course_id', $course->id)
        ->first();
}

/**
 * Get completed courses count.
 */
public function getCompletedCoursesCountAttribute(): int
{
    return $this->enrollments()
        ->where('status', Enrollment::STATUS_COMPLETED)
        ->count();
}

/**
 * Get total learning time in seconds.
 */
public function getTotalLearningTimeAttribute(): int
{
    return $this->progress()->sum('time_spent_seconds');
}

/**
 * Get formatted total learning time.
 */
public function getFormattedTotalLearningTimeAttribute(): string
{
    $seconds = $this->total_learning_time;
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);

    if ($hours > 0) {
        return "{$hours}h {$minutes}m";
    }

    return "{$minutes}m";
}

/**
 * Get average progress across all enrollments.
 */
public function getAverageProgressAttribute(): float
{
    return $this->enrollments()->avg('progress_percent') ?? 0;
}

/**
 * Check if user has completed a course.
 */
public function hasCompleted(Course $course): bool
{
    $enrollment = $this->getProgressFor($course);
    return $enrollment && $enrollment->isCompleted();
}

/**
 * Get certificates count.
 */
public function getCertificatesCountAttribute(): int
{
    return $this->certificates()->count();
}

/**
 * Get statistics for this user.
 */
public function getLearningStatsAttribute(): array
{
    return [
        'enrolled_courses' => $this->enrollments()->count(),
        'completed_courses' => $this->completed_courses_count,
        'in_progress_courses' => $this->enrollments()->InProgress()->count(),
        'average_progress' => round($this->average_progress, 1),
        'total_learning_time' => $this->formatted_total_learning_time,
        'certificates' => $this->certificates_count,
        'exam_attempts' => $this->examAttempts()->count(),
        'passed_exams' => $this->examResults()->where('passed', true)->count(),
    ];
}

}
