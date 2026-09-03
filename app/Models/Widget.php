<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'type', 'zone', 'content', 'position', 'is_active', 'order'];

    protected $casts = [
        'content'   => 'array',
        'is_active' => 'boolean',
        'position'  => 'integer',
        'order'     => 'integer',
    ];

    public function scopeActive($q)   { return $q->where('is_active', true); }
    public function scopeInZone($q, $z) { return $q->where('zone', $z); }
    public function scopeOrdered($q) { return $q->orderBy('position')->orderBy('order'); }
}
