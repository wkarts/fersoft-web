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
            'surface_alt' => '#FAFCFC',
            'muted' => '#62777D',
            'accent_soft' => '#EAF5EF',
            'hover' => '#EEF4F2',
            'nav_active_bg' => '#EAF5EF',
            'nav_active_text' => '#1F9866',
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
            'surface_alt' => '#FAFCFC',
            'muted' => '#62777D',
            'accent_soft' => '#EAF5EF',
            'hover' => '#EEF4F2',
            'nav_active_bg' => '#20463D',
            'nav_active_text' => '#A0E8C2',
            'dark' => false,
            'density' => 'comfortable',
        ],
        'fer-night' => [
            'name' => 'FERSOFT Noturno',
            'description' => 'Modo escuro completo, com contraste ajustado.',
            'accent' => '#62D3AB',
            'accent_hover' => '#8FDAB7',
            'background' => '#11252C',
            'surface' => '#1C333B',
            'sidebar' => '#172D35',
            'sidebar_text' => '#BECFCE',
            'text' => '#EEF6F4',
            'border' => '#38535C',
            'surface_alt' => '#223B43',
            'muted' => '#B7C8CB',
            'accent_soft' => '#23483D',
            'hover' => '#2A4850',
            'nav_active_bg' => '#25483E',
            'nav_active_text' => '#62D3AB',
            'dark' => true,
            'density' => 'comfortable',
        ],
    ],
];

