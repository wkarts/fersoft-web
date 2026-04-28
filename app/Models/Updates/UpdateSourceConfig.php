<?php

namespace App\Models\Updates;

use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UpdateSourceConfig extends BaseModel
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
