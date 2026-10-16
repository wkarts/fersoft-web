<?php

// O tema aprovado é o padrão. APP_UI_THEME=classic mantém o rollback visual.
// Preferências do banco ajustam apenas as opções autorizadas por empresa/usuário.
$mode = strtolower(trim((string) env('APP_UI_THEME', 'refined')));
$active = $mode === 'refined';

return [
    'name' => $active ? 'refined' : 'classic',
    'active' => $active,
    'default_preset' => 'fer-light',
    'support_url' => env('HUB_BASE_URL', ''),
    'support_token' => env('HUB_TOKEN', ''),
    'support_phone' => env('CONTATO_SUPORTE', ''),
    // Presets imutáveis disponíveis por padrão. Temas personalizados só aparecem
    // após publicação pelo superadmin e liberação à empresa.
    'presets' => [
        'fer-light' => [
            'name' => 'FERSOFT Claro',
            'description' => 'Superfícies claras e navegação elegante.',
            'accent' => '#1F9866',
            'accent_hover' => '#15754F',
            'background' => '#F5F7F7',
            'surface' => '#FFFFFF',
            'sidebar' => '#FFFFFF',
            'sidebar_text' => '#566C72',
            'text' => '#182F36',
            'border' => '#DCE7E7',
            'dark' => false,
            'density' => 'comfortable',
        ],
        'fer-slate' => [
            'name' => 'FERSOFT Slate',
            'description' => 'Menu noturno com páginas claras.',
            'accent' => '#1F9866',
            'accent_hover' => '#15754F',
            'background' => '#F5F7F7',
            'surface' => '#FFFFFF',
            'sidebar' => '#1D2639',
            'sidebar_text' => '#B9C7D8',
            'text' => '#182F36',
            'border' => '#DCE7E7',
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

