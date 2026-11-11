<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAudienceGroupMember extends Model
{
    protected $fillable = ['group_id', 'email', 'name'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(EmailAudienceGroup::class, 'group_id');
    }
}
