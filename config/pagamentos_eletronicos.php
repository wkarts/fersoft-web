<?php

return [
    'provedores' => [
        'asaas' => [
            'nome' => 'Asaas',
            'parametros' => App\Services\Pagamentos\ParametrosAsaas::class,
            'formulario' => 'pagamentos.parametros.asaas',
        ],
    ],
];
