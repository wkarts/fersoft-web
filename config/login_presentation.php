<?php

return [
    // Único seletor de acesso configurado no .env.
    // O login histórico não depende dos serviços de aparência.
    'page' => env('PAG_LOGIN', 'access'),

    // Padrões internos, utilizados somente até a primeira publicação.
    // Modelos, fontes e identidade visual são administrados em /config/aparencia.
    'initial_layout' => 'split',
    'initial_font' => 'poppins',
    'initial_tagline' => env('APP_DESC', ''),
];
