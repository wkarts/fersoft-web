<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fechamentos_mensais')) {
            throw new \RuntimeException('Tabela ARGWS ausente: fechamentos_mensais');
        }

        if (!Schema::hasColumn('fechamentos_mensais', 'filial_id')) {
            Schema::table('fechamentos_mensais', function (Blueprint $table) {
                $table->integer('filial_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Rollback conservador: nenhum DROP e nenhuma remoção de dados ou de campos.
        // Os objetos acrescentados permanecem disponíveis após migrate:rollback.
    }
};
