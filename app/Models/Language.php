<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Language extends Model {
    public $timestamps = false; // Souvent pas besoin de timestamp pour les langues
    protected $fillable =['name', 'code', 'is_active', 'is_default'];
}
