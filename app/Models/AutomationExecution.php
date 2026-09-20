<?php

namespace App\Models;

class AutomationExecution extends BaseModel
{
    protected $table = 'automation_executions';

    protected $fillable = [
        'automation_rule_id',
        'empresa_id',
        'trigger',
        'connect_api_webhook_event_id',
        'status',
        'started_at',
        'finished_at',
        'error',
        'context',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'context' => 'array',
    ];
}
