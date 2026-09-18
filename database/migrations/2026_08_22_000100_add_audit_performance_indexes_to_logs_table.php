<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            // Listagem padrão: tenant + ordenação temporal/paginação.
            // Um único índice composto resolve o caminho mais caro sem inflar
            // desnecessariamente o custo de escrita da auditoria.
            $table->index(['empresa_id', 'created_at', 'id'], 'logs_empresa_created_id_idx');

            // Filtro/distinct de ação por tenant.
            $table->index(['empresa_id', 'acao'], 'logs_empresa_acao_idx');
        });
    }

    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->dropIndex('logs_empresa_created_id_idx');
            $table->dropIndex('logs_empresa_acao_idx');
        });
    }
};
