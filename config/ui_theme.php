<?php

// Uma única chave escolhe a arquitetura visual. Nenhuma preferência do banco
// pode impor o tema experimental quando APP_UI_THEME não é refined.
$mode = strtolower(trim((string) env('APP_UI_THEME', 'classic')));
$active = $mode === 'refined';

return [
    'name' => $active ? 'refined' : 'classic',
    'active' => $active,
    'default_preset' => 'fer-slate',
    'support_url' => env('HUB_BASE_URL', ''),
    'support_token' => env('HUB_TOKEN', ''),
    'support_phone' => env('CONTATO_SUPORTE', ''),
    // Presets imutáveis disponíveis por padrão. Temas personalizados só aparecem
    // após publicação pelo superadmin e liberação à empresa.
    'presets' => [
        'fer-light' => [
            'name' => 'FERSOFT Claro',
            'description' => 'Superfícies claras e navegação elegante.',
            'accent' => '#168F81',
            'accent_hover' => '#11786D',
            'background' => '#F5F7FB',
            'surface' => '#FFFFFF',
            'sidebar' => '#FFFFFF',
            'sidebar_text' => '#24334E',
            'text' => '#1C2A40',
            'border' => '#DEE5EF',
            'dark' => false,
            'density' => 'comfortable',
        ],
        'fer-slate' => [
            'name' => 'FERSOFT Slate',
            'description' => 'Menu noturno com páginas claras.',
            'accent' => '#2671E8',
            'accent_hover' => '#1B57BC',
            'background' => '#F5F7FB',
            'surface' => '#FFFFFF',
            'sidebar' => '#1C2030',
            'sidebar_text' => '#D9E2F2',
            'text' => '#202B40',
            'border' => '#E0E6F1',
            'dark' => false,
            'density' => 'comfortable',
        ],
        'fer-night' => [
            'name' => 'FERSOFT Noturno',
            'description' => 'Modo escuro completo, com contraste ajustado.',
            'accent' => '#59BDB0',
            'accent_hover' => '#79D6CA',
            'background' => '#111C2A',
            'surface' => '#1A2B3A',
            'sidebar' => '#111827',
            'sidebar_text' => '#E1EAF4',
            'text' => '#EDF4FC',
            'border' => '#33475C',
            'dark' => true,
            'density' => 'comfortable',
        ],
    ],
];
