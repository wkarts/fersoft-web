<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdpDevice extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'adp_devices';

    protected $fillable = [
        'empresa_id', 'integrador_config_id', 'device_uuid', 'device_type', 'name', 'model', 'driver',
        'protocol', 'host', 'port', 'baud_rate', 'status', 'supports_stream', 'supports_snapshot',
        'metadata_json', 'last_seen_at', 'ativo',
    ];

    protected $casts = [
        'supports_stream' => 'boolean',
        'supports_snapshot' => 'boolean',
        'metadata_json' => 'array',
        'last_seen_at' => 'datetime',
        'ativo' => 'boolean',
    ];
}
