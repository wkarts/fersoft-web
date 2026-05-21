<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdpCamera extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'adp_cameras';

    protected $fillable = [
        'empresa_id',
        'integrador_config_id',
        'camera_uuid',
        'descricao',
        'name',
        'model',
        'driver',
        'protocol',
        'host',
        'port',
        'stream_url',
        'snapshot_url',
        'supports_stream',
        'supports_snapshot',
        'status',
        'camera_access_mode',
        'ultimo_status_em',
        'ativo',
        'metadata_json',
    ];

    protected $casts = [
        'supports_stream' => 'boolean',
        'supports_snapshot' => 'boolean',
        'ultimo_status_em' => 'datetime',
        'ativo' => 'boolean',
        'metadata_json' => 'array',
    ];
}
