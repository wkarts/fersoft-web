<?php

/**
 * Configuração do assistente fiscal via Connect|API.
 * APP_NAME permanece sob responsabilidade de config/app.php.
 * Credenciais não devem ser armazenadas no código-fonte.
 */
return [
    'ai' => [
        'api_key' => env('GEMINI_API_KEY'),
        // Configurável pelo .env; preserva o modelo original como fallback.
        'model' => env('GEMINI_MODEL', 'gemini-3.8-flash'),
    ],


    'midia' => [
        'max_xml_bytes' => (int) env('WHATSAPP_NFE_MAX_XML_BYTES', 4194304),
        'max_audio_bytes' => (int) env('WHATSAPP_NFE_MAX_AUDIO_BYTES', 8388608),
        // Domínios HTTPS autorizados para URLs de mídia vindas do evento.
        'allowed_hosts' => array_values(array_filter(array_map('trim',
            explode(',', (string) env('WHATSAPP_NFE_MEDIA_ALLOWED_HOSTS', ''))
        ))),
    ],

];
