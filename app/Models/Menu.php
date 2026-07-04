<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model {
    protected $fillable = ['name', 'location', 'items'];
    protected $casts = ['items' => 'array']; // Stocker les liens du menu en JSON
}
