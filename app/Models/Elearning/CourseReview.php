<?php

namespace App\Models\Elearning;

use Illuminate\Database\Eloquent\Model;

class CourseReview extends Model
{
    protected $table = 'course_reviews';
    protected $fillable = ['user_id', 'course_id', 'rating', 'title', 'comment', 'is_approved'];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
