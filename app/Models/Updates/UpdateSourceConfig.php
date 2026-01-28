<?php

namespace App\Models\Updates;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpdateSourceConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'name',
        'credentials',
        'options',
        'active',
    ];

    protected $casts = [
        'credentials' => 'array',
        'options' => 'array',
        'active' => 'boolean',
    ];
}
