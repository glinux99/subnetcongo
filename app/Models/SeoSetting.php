<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type', 'url_pattern', 'is_active'];

    protected $casts = [
        'value'     => 'array',
        'is_active' => 'boolean',
    ];

    public static function global(): array
    {
        $row = static::where('key', 'global')->first();
        $defaults = [
            'site_name' => config('app.name'),
            'separator' => '|',
            'default_description' => '',
            'default_keywords' => '',
            'twitter_handle' => '',
            'og_image' => null,
        ];
        if (! $row) return $defaults;
        $val = $row->value;
        return is_array($val) ? array_merge($defaults, $val) : $defaults;
    }
}
