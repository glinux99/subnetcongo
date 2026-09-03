<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingSlide extends Model
{
    use HasFactory;

    protected $fillable = [
        'badge', 'title', 'highlight', 'description', 'bg_image', 'main_image',
        'icon', 'card_title', 'card_sub', 'cta_label', 'cta_url',
        'position', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position'  => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('position')->orderBy('id');
    }
}
