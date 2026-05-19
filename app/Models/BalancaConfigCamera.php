<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BalancaConfigCamera extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'balanca_config_cameras';

    protected $fillable = [
        'empresa_id',
        'balanca_config_id',
        'camera_uuid',
        'camera_nome',
        'camera_driver',
        'camera_protocol',
        'ordem',
        'ativo',
        'metadata_json',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'metadata_json' => 'array',
    ];
}

