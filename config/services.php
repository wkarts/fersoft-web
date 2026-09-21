<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'nfse_saatri' => [
        'username' => env('NFSE_SAATRI_USERNAME'),
        'password' => env('NFSE_SAATRI_PASSWORD'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'tracking' => [
        // Google Analytics 4
        'google_analytics' => env('GA_MEASUREMENT_ID', 'G-XXXXXXX'),

        // Hotjar
        'hotjar' => env('HOTJAR_ID', ''),

        // PostHog (self-host ou cloud)
        'posthog' => [
            // API key do projeto PostHog
            'key'  => env('POSTHOG_KEY', ''),

            // Host do PostHog: ex: https://posthog.suaempresa.com  (sem barra no final)
            'host' => rtrim(env('POSTHOG_HOST', ''), '/'),

            // Opções comuns do snippet JS
            // (mantidas aqui para você habilitar/ajustar via .env sem mexer na view)
            'autocapture'        => (bool) env('POSTHOG_AUTOCAPTURE', true),
            'capture_pageview'   => (bool) env('POSTHOG_CAPTURE_PAGEVIEW', true),
            'debug'              => (bool) env('POSTHOG_DEBUG', false),

            // Session Recording (replay)
            'session_recording'  => [
                'enabled'         => (bool) env('POSTHOG_SESSION_RECORDING_ENABLED', true),
                // Máscaras comuns; ajuste conforme LGPD/privacidade
                'maskAllInputs'   => (bool) env('POSTHOG_SR_MASK_ALL_INPUTS', true),
                'captureCanvas'   => (bool) env('POSTHOG_SR_CAPTURE_CANVAS', false),
                // Sampling (0–1) se quiser amostragem (ex.: 0.2 = 20%)
                'sampling'        => env('POSTHOG_SR_SAMPLING', null), // null = padrão do PostHog
            ],

            // Identify automático do usuário autenticado (executa na view com @auth)
            'identify_enabled'   => (bool) env('POSTHOG_IDENTIFY_ENABLED', true),
        ],
    ],
];
