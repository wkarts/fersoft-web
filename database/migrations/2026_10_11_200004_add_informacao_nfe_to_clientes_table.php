<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('clientes')) {
            throw new \RuntimeException('Tabela ARGWS ausente: clientes');
        }

        if (!Schema::hasColumn('clientes', 'informacao_nfe')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->text('informacao_nfe')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Rollback conservador: nenhum DROP e nenhuma remoção de dados ou de campos.
        // Os objetos acrescentados permanecem disponíveis após migrate:rollback.
    }
};
