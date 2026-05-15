<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FuncionarioModeloExport implements FromArray, WithHeadings
{
    public function array(): array {
        return []; // Retorna vazio, pois queremos apenas o cabeçalho
    }

    public function headings(): array {
        return [
            'nome',
            'cpf',
            'rg',
            'funcao',
            'telefone',
            'endereco',
            'numero',
            'bairro',
            'email',
            'salario',
            'data_nascimento',
            'data_admissao',
            'data_registro',
            'cod_func'
        ];
    }
}