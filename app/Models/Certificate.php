<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Certificate extends Model {
    protected $fillable = [
        'uuid',
        'user_id',
        'course_id',
        'learning_path_id',
        'template_id',
        'certificate_code',
        'score',
        'status',
        'issued_at',
        'expires_at',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
        'verification_hash',
        'metadata',
        'file_path'
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
    public function learningPath(): BelongsTo { return $this->belongsTo(LearningPath::class); }
    public function template(): BelongsTo { return $this->belongsTo(CertificateTemplate::class); }

    /** @return HasMany */
    public function verifications(): HasMany { return $this->hasMany(CertificateVerification::class); }
}
