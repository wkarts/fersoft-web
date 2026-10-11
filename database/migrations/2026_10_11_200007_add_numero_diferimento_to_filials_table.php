<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('filials')) {
            throw new \RuntimeException('Tabela ARGWS ausente: filials');
        }

        if (!Schema::hasColumn('filials', 'numero_diferimento')) {
            Schema::table('filials', function (Blueprint $table) {
                $table->string('numero_diferimento', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Rollback conservador: nenhum DROP e nenhuma remoção de dados ou de campos.
        // Os objetos acrescentados permanecem disponíveis após migrate:rollback.
    }
};
