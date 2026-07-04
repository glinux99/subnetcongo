<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'user_id',
        'rating',
        'comment',
        'is_approved',
    ];

    protected function casts(): array
    {
        return [
            'rating'      => 'integer',
            'is_approved' => 'boolean',
        ];
    }

    // ------------------------------------------------------------------
    // RELATIONS
    // ------------------------------------------------------------------

    /**
     * Le cours concerné par cet avis.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * L'utilisateur (étudiant) ayant laissé l'avis.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ------------------------------------------------------------------
    // SCOPES PRATIQUES
    // ------------------------------------------------------------------

    /**
     * Ne récupère que les avis approuvés.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Avis les mieux notés (ordre décroissant).
     */
    public function scopeTopRated($query)
    {
        return $query->orderBy('rating', 'desc');
    }
}
