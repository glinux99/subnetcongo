<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Page extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'title',
        'slug',
        'view_path',
        'content',
        'is_published',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'content' => 'array', // Utile si vous utilisez un éditeur de blocs ou JSON
    ];

    /**
     * Utiliser le slug pour le routing au lieu de l'ID.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
}
