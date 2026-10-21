<?php

namespace App\Models\Community;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumCategory extends Model
{
    use HasFactory;

    /**
     * Les attributs assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'order',
        'is_active'
    ];

    /**
     * Relation avec les sujets (topics) du forum.
     * Résout l'erreur : Call to undefined method App\Models\ForumCategory::topics()
     */
    public function topics(): HasMany
    {
        return $this->hasMany(ForumTopic::class, 'forum_category_id');
    }
}
