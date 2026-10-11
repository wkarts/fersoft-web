<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('funcionarios')) {
            throw new \RuntimeException('Tabela ARGWS ausente: funcionarios');
        }

        if (!Schema::hasColumn('funcionarios', 'permissao_nfe_whatsapp')) {
            Schema::table('funcionarios', function (Blueprint $table) {
                $table->boolean('permissao_nfe_whatsapp')->default(false);
            });
        }
    }

    public function down(): void
    {
        // Rollback conservador: nenhum DROP e nenhuma remoção de dados ou de campos.
        // Os objetos acrescentados permanecem disponíveis após migrate:rollback.
    }
};
