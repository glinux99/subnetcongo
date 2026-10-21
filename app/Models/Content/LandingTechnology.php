<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingTechnology extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'icon', 'color', 'category', 'description',
        'position', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position'  => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('position')->orderBy('name');
    }
}
