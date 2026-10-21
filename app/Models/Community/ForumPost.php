<?php

namespace App\Models\Community;
use Illuminate\Database\Eloquent\Model;

class ForumPost extends Model {
    protected $fillable = [
        'forum_topic_id',
        'user_id',
        'content',
        'attachment_path',
        'attachment_name'
    ];

    public function topic() { return $this->belongsTo(ForumTopic::class, 'forum_topic_id'); }

    public function author() { return $this->belongsTo(User::class, 'user_id'); }
}
