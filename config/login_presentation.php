<?php

return [
    // Usado somente antes da primeira publicação administrativa.
    // Após publicar um modelo, apenas o registro publicado define a aparência.
    // Usa a seleção histórica, mas funciona com o cache de configuração Laravel.
    // Isolamento estrito. As telas históricas permanecem independentes.
    'enabled' => filter_var(env('LOGIN_PRESENTATION_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    // A identidade global dos menus é uma opção independente (desligada por padrão).
    'global_branding_enabled' => filter_var(env('LOGIN_PRESENTATION_GLOBAL_BRANDING', false), FILTER_VALIDATE_BOOLEAN),
    'page' => env('PAG_LOGIN', 'access'),
    'initial_layout' => env('APP_LOGIN_LAYOUT', 'split'),
    'initial_font' => env('APP_LOGIN_FONT', 'poppins'),
    'initial_tagline' => env('APP_DESC', ''),
];
