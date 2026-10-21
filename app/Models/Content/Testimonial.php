<?php

namespace App\Models\Content;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model {
    protected $fillable =['user_id', 'course_id', 'content', 'rating', 'is_approved'];

    public function user() { return $this->belongsTo(User::class); }
    public function course() { return $this->belongsTo(Course::class); }
}
