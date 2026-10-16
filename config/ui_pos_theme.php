<?php

// O PDV conserva sua aparência operacional mesmo que o tema pessoal do ERP mude.
// O padrão reproduz a paleta clara já usada pelo PDV antes deste catálogo.
return [
    'default_preset' => 'pos-default',
    'presets' => [
        'pos-default' => [
            'name' => 'Padrão do PDV',
            'description' => 'Aparência operacional atual.',
            'accent' => '#1F9866', 'accent_hover' => '#15754F',
            'background' => '#F5F7F7', 'surface' => '#FFFFFF',
            'text' => '#182F36', 'border' => '#DCE7E7',
            'muted' => '#62777D', 'surface_alt' => '#FAFCFC',
            'accent_soft' => '#EAF5EF', 'dark' => false,
        ],
        'pos-soft' => [
            'name' => 'Verde suave',
            'description' => 'Variação clara para a operação do caixa.',
            'accent' => '#167D5B', 'accent_hover' => '#11694B',
            'background' => '#F2F7F4', 'surface' => '#FFFFFF',
            'text' => '#19362D', 'border' => '#D7E7DC',
            'muted' => '#5F766A', 'surface_alt' => '#F8FBF9',
            'accent_soft' => '#E1F0E6', 'dark' => false,
        ],
        'pos-night' => [
            'name' => 'Noturno',
            'description' => 'Superfícies escuras para o caixa.',
            'accent' => '#75D9B6', 'accent_hover' => '#A2EAD0',
            'background' => '#171C25', 'surface' => '#222B36',
            'text' => '#F3F6FA', 'border' => '#3B4857',
            'muted' => '#B8C8D7', 'surface_alt' => '#283340',
            'accent_soft' => '#173F3C', 'dark' => true,
        ],
    ],
];
