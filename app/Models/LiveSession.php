<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LiveSession extends Model {
    protected $fillable =['course_id', 'instructor_id', 'title', 'start_time', 'duration_minutes', 'meeting_url', 'status'];

    public function course() { return $this->belongsTo(Course::class); }
    public function instructor() { return $this->belongsTo(User::class, 'instructor_id'); }

    public function attendees()
    {
        return $this->belongsToMany(User::class, 'live_session_user')->withPivot('joined_at', 'left_at')->withTimestamps();
    }
}
