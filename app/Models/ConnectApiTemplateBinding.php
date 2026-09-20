<?php

namespace App\Models;

class ConnectApiTemplateBinding extends BaseModel
{
    protected $table = 'connect_api_template_bindings';

    protected $fillable = [
        'empresa_id',
        'event_key',
        'template_name',
        'language',
        'template_version',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'template_version' => 'integer',
    ];
}
