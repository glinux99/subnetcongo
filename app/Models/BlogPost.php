<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model {
    protected $fillable =['author_id', 'title', 'slug', 'content', 'thumbnail', 'status', 'published_at'];

    public function author() { return $this->belongsTo(User::class, 'author_id'); }
}
