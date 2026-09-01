<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Resource extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'resourceable_id',
        'resourceable_type',
        'title',
        'slug',
        'path',
        'file_type',
        'mime_type',
        'size',
        'is_active',
        'is_external',
        'sort_order',
        'uploaded_by',
        'download_count',
        'access_level'
    ];

    protected $appends = ['full_url', 'human_size'];

    /**
     * RELATION POLYMORPHIQUE
     */
    public function resourceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * ACCESSEUR : URL complète du fichier
     */
    public function getFullUrlAttribute(): string
    {
        if ($this->is_external) {
            return $this->path;
        }
        return Storage::disk('media')->url($this->path);
    }

    /**
     * ACCESSEUR : Taille lisible (ex: 2.4 MB)
     * Correspond au champ 'size' que vous utilisez dans votre JS
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}