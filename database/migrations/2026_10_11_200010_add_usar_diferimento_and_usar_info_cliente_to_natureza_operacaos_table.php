<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('natureza_operacaos')) {
            throw new \RuntimeException('Tabela ARGWS ausente: natureza_operacaos');
        }

        if (!Schema::hasColumn('natureza_operacaos', 'usar_diferimento')) {
            Schema::table('natureza_operacaos', function (Blueprint $table) {
                $table->boolean('usar_diferimento')->default(false)->comment('Puxa o numero de diferimento para as obs da NFe');
            });
        }

        if (!Schema::hasColumn('natureza_operacaos', 'usar_info_cliente')) {
            Schema::table('natureza_operacaos', function (Blueprint $table) {
                $table->boolean('usar_info_cliente')->default(false)->comment('Puxa a informação fixa do cliente para as obs da NFe');
            });
        }
    }

    public function down(): void
    {
        // Rollback conservador: nenhum DROP e nenhuma remoção de dados ou de campos.
        // Os objetos acrescentados permanecem disponíveis após migrate:rollback.
    }
};
