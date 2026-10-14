<?php

// Seletor exclusivo de apresentação do ERP interno.
// APP_UI_THEME=classic mantém tudo como hoje; refined ativa o novo tema.
// O login (PAG_LOGIN) e o tema legado por usuário ($tema) são independentes.
$mode = strtolower(trim((string) env('APP_UI_THEME', 'classic')));
$active = $mode === 'refined';

return [
    'name' => $active ? 'refined' : 'classic',
    'active' => $active,
];
