<?php

return [
    'base_url' => env('CONNECT_API_BASE_URL', ''),
    'bootstrap_key' => env('CONNECT_API_BOOTSTRAP_KEY', ''),
    'timeout' => (int) env('CONNECT_API_TIMEOUT', 30),
    'connect_timeout' => (int) env('CONNECT_API_CONNECT_TIMEOUT', 10),
    'ddi' => env('CONNECT_API_DDI', '55'),
    'ddd' => env('CONNECT_API_DDD', '75'),
    'instance_prefix' => env('CONNECT_API_INSTANCE_PREFIX', ''),
    'default_templates' => [
        'ponto_boas_vindas' => [
            'event_key' => 'ponto.boas_vindas',
            'language' => 'pt_BR',
            'body' => 'Olá, {{1}}! Sua integração de ponto está ativa. Envie OI para iniciar.',
        ],
        'ponto_registrado' => [
            'event_key' => 'ponto.registro.confirmado',
            'language' => 'pt_BR',
            'body' => 'Olá, {{1}}! Seu ponto {{2}} foi registrado em {{3}}. Comprovante: {{4}}.',
        ],
        'frota_checklist' => [
            'event_key' => 'frota.checklist',
            'language' => 'pt_BR',
            'body' => 'Olá, {{1}}! O checklist do veículo {{2}} está disponível em {{3}}.',
        ],
        'coleta_confirmada' => [
            'event_key' => 'coleta.confirmada',
            'language' => 'pt_BR',
            'body' => 'Olá, {{1}}! Sua coleta foi confirmada para {{2}}.',
        ],
        'pesagem_concluida' => [
            'event_key' => 'pesagem.concluida',
            'language' => 'pt_BR',
            'body' => 'A pesagem {{1}} foi concluída. Peso líquido: {{2}} kg.',
        ],
    ],
    'webhook_events' => [
        'MESSAGES_UPSERT',
        'MESSAGES_UPDATE',
        'MESSAGES_DELETE',
        'CONNECTION_UPDATE',
        'QRCODE_UPDATED',
        'CONTACTS_UPSERT',
        'LOGOUT_INSTANCE',
        'REMOVE_INSTANCE',
    ],
];
