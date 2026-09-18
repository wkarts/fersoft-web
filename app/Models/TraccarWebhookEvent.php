<?php

namespace App\Models;

use Carbon\Carbon;

class TraccarWebhookEvent extends BaseModel
{
    public const STATUS_RECEBIDO = 'recebido';
    public const STATUS_PROCESSANDO = 'processando';
    public const STATUS_CLASSIFICADO = 'classificado';
    public const STATUS_CONSUMIDO = 'consumido';
    public const STATUS_IGNORADO = 'ignorado';
    public const STATUS_RETRY = 'retry';
    public const STATUS_FALHOU = 'falhou';

    protected $table = 'traccar_webhook_events';

    protected $fillable = [
        'dedupe_key',
        'traccar_event_id',
        'empresa_id',
        'movimentacao_id',
        'etapa',
        'event_type',
        'device_id',
        'unique_id',
        'position_id',
        'geofence_id',
        'event_time',
        'latitude',
        'longitude',
        'speed',
        'status',
        'attempts',
        'next_retry_at',
        'locked_at',
        'processed_at',
        'last_error',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'event_time' => 'datetime',
        'next_retry_at' => 'datetime',
        'locked_at' => 'datetime',
        'processed_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'speed' => 'float',
        'attempts' => 'integer',
    ];

    public function movimentacao()
    {
        return $this->belongsTo(
            MovimentacaoVeiculo::class,
            'movimentacao_id'
        );
    }

    /**
     * Formato esperado pelo MonitorarFrota, preservando a integração
     * anterior que lia o evento do Cache.
     */
    public function toMonitorPayload(): array
    {
        return [
            'persisted_event_id' => $this->id,
            'event_id' => $this->traccar_event_id,
            'event_type' => $this->event_type,
            'event_time' => $this->event_time
                ? Carbon::parse($this->event_time)->toIso8601String()
                : null,
            'device_id' => $this->device_id,
            'unique_id' => $this->unique_id,
            'position_id' => $this->position_id,
            'geofence_id' => $this->geofence_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'speed' => $this->speed,
            'received_at' => $this->created_at
                ? Carbon::parse($this->created_at)->toIso8601String()
                : null,
        ];
    }
}
