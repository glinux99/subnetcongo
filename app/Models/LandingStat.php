<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingStat extends Model
{
    use HasFactory;

    protected $fillable = ['label', 'value', 'icon', 'color', 'position', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'position'  => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('position')->orderBy('id');
    }
}
