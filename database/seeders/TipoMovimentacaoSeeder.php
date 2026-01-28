<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoMovimentacao;

class TipoMovimentacaoSeeder extends Seeder
{
    public function run()
    {
        TipoMovimentacao::create(['nome' => 'Transferência', 'descricao' => 'Movimentação de veículo entre motoristas.', 'ativo' => true]);
        TipoMovimentacao::create(['nome' => 'Abastecimento', 'descricao' => 'Registro de abastecimento do veículo.', 'ativo' => true]);
        TipoMovimentacao::create(['nome' => 'Manutenção', 'descricao' => 'Registro de manutenção no veículo.', 'ativo' => true]);
    }
};
