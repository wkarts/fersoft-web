<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UiThemePreset extends Model
{
    protected $table = 'ui_theme_presets';

    protected $fillable = [
        'slug', 'name', 'description', 'colors', 'sidebar_style',
        'density', 'status', 'created_by', 'published_at',
    ];

    protected $casts = [
        'colors' => 'array',
        'published_at' => 'datetime',
    ];
}
