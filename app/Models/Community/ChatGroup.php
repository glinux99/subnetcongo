<?php

namespace App\Models\Community;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'cover_path',
        'creator_id'
    ];

    /**
     * Get the user who created the group.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the members of the chat group.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_group_user', 'chat_group_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Get the messages in this group.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'group_id');
    }
}

