<?php

return [
    // Usado somente antes da primeira publicação administrativa.
    // Após publicar um modelo, apenas o registro publicado define a aparência.
    // Usa a seleção histórica, mas funciona com o cache de configuração Laravel.
    'page' => env('PAG_LOGIN', 'access'),
    'initial_layout' => env('APP_LOGIN_LAYOUT', 'split'),
    'initial_font' => env('APP_LOGIN_FONT', 'poppins'),
    'initial_tagline' => env('APP_DESC', ''),
];
