<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UiPosThemePreset extends Model
{
    protected $table = 'ui_pos_theme_presets';

    protected $fillable = ['slug', 'name', 'description', 'colors', 'status', 'created_by', 'published_at'];

    protected $casts = ['colors' => 'array', 'published_at' => 'datetime'];
}
