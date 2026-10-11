<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('movimentacoes_veiculos')) {
            throw new \RuntimeException('Tabela ARGWS ausente: movimentacoes_veiculos');
        }

        if (!Schema::hasColumn('movimentacoes_veiculos', 'solicitar_abastecimento')) {
            Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
                $table->boolean('solicitar_abastecimento')->default(false);
            });
        }
    }

    public function down(): void
    {
        // Rollback conservador: nenhum DROP e nenhuma remoção de dados ou de campos.
        // Os objetos acrescentados permanecem disponíveis após migrate:rollback.
    }
};
