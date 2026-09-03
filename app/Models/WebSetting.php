<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebSetting extends Model
{
    use HasFactory;

    protected $table = 'web_settings';

    protected $fillable = ['key', 'value', 'group', 'type'];

    /**
     * Récupère une valeur par clé (renvoie la valeur par défaut si absente).
     */
    public static function get(string $key, $default = null)
    {
        $row = static::where('key', $key)->first();
        return $row?->value ?? $default;
    }

    /**
     * Définit une valeur (upsert).
     */
    public static function set(string $key, $value, string $group = 'general', string $type = 'text'): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) || is_object($value) ? json_encode($value) : (string) $value, 'group' => $group, 'type' => $type]
        );
    }

    /**
     * Récupère tout un groupe sous forme de tableau [key => value].
     */
    public static function group(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(fn ($r) => [$r->key => $r->value])
            ->toArray();
    }
}
