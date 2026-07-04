<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model {
    // Désactiver les timestamps si ta table settings n'a pas created_at et updated_at
    // public $timestamps = false;

    protected $fillable = ['key', 'value', 'type', 'group','user_id'];
}

