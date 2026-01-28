<?php

return [
    // DDI, DDD, base URL e token global virão do .env
    'ddi'        => env('EVO_DDI', '55'),
    'ddd'        => env('EVO_DDD', '75'),
    'base_url'   => env('EVO_BASE_URL'),
    'global_api' => env('EVO_GLOBAL_API'),
    'version'    => env('EVO_API_VERSION', 'V1'), // 'V1' ou 'V2'
    'qr_logo_base64' => env('EVO_QR_LOGO_BASE64', null),
    'qr_logo_size'   => env('EVO_QR_LOGO_SIZE', 0.2),
    'token_prefix' => env('EVO_TOKEN_PREFIX', '$' . env('APP_NAME') . 'tk_'),
    'token_alphabet' => env('EVO_TOKEN_ALPHABET','ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'),
];
