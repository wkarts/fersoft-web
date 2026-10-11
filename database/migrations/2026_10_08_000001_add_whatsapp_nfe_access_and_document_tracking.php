<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('usuarios', 'permissao_nfe_whatsapp')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->boolean('permissao_nfe_whatsapp')->default(false);
            });
        }

        if (!Schema::hasTable('whatsapp_nfe_documentos_envios')) {
            Schema::create('whatsapp_nfe_documentos_envios', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id');
                $table->unsignedBigInteger('venda_id');
                $table->string('numero_destino', 30);
                $table->string('tipo', 10);
                $table->string('provider_message_id', 160)->nullable();
                $table->timestamp('enviado_em')->nullable();
                $table->nullableTimestamps();
                $table->unique(['empresa_id', 'venda_id', 'numero_destino', 'tipo'], 'wa_nfe_documento_unico');
                $table->index(['empresa_id', 'venda_id']);
            });
        }
    }

    public function down(): void
    {
        // Rollback conservador: não apaga permissões nem trilhas de envio,
        // porque a instalação pode já possuir estes dados antes da migration.
        // A remoção, se necessária, deve ser uma operação explícita e auditada.
    }
};
