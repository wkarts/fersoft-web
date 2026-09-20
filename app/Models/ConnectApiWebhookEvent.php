<?php

namespace App\Models;

class ConnectApiWebhookEvent extends BaseModel
{
    protected $table = 'connect_api_webhook_events';

    protected $fillable = [
        'connect_api_instance_id',
        'empresa_id',
        'event_type',
        'external_event_id',
        'message_id',
        'deduplication_key',
        'payload',
        'normalized_payload',
        'status',
        'attempts',
        'next_retry_at',
        'received_at',
        'processing_at',
        'processed_at',
        'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'normalized_payload' => 'array',
        'attempts' => 'integer',
        'next_retry_at' => 'datetime',
        'received_at' => 'datetime',
        'processing_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function instance()
    {
        return $this->belongsTo(ConnectApiInstance::class, 'connect_api_instance_id');
    }
}
