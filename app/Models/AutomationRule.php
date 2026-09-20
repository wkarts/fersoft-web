<?php

namespace App\Models;

class AutomationRule extends BaseModel
{
    protected $table = 'automation_rules';

    protected $fillable = [
        'empresa_id',
        'name',
        'description',
        'trigger',
        'enabled',
        'priority',
        'stop_on_success',
        'conditions',
        'actions',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'stop_on_success' => 'boolean',
        'conditions' => 'array',
        'actions' => 'array',
        'priority' => 'integer',
    ];
}
