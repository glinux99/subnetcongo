<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Realization extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_published',
        'title',
        'slug',
        'summary',
        'description',
        'image',
        'cover_image',
        'client',
        'location',
        'completed_at',
        'category',
        'tags',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'completed_at' => 'date',
        'tags' => 'array',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
