<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_documento_envios')) {
            foreach (['id', 'empresa_id', 'venda_id', 'numero_destino', 'tipo', 'arquivo_nome', 'arquivo_hash', 'provider_message_id', 'enviado_em', 'created_at', 'updated_at'] as $column) {
                if (!Schema::hasColumn('whatsapp_documento_envios', $column)) {
                    throw new \RuntimeException('A tabela whatsapp_documento_envios já existe, porém está incompleta: ' . $column);
                }
            }
            return;
        }
        Schema::create('whatsapp_documento_envios', function (Blueprint $table) {
            $table->bigIncrements('id');
            // Tipos preservados do Metal. NÃO criar FK: empresas.id/vendas.id
            // são INT UNSIGNED no ARGWS, enquanto estes campos são BIGINT UNSIGNED.
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('venda_id');
            $table->string('numero_destino', 30);
            $table->string('tipo', 20);
            $table->string('arquivo_nome', 255);
            $table->char('arquivo_hash', 64)->nullable();
            $table->string('provider_message_id', 120)->nullable();
            $table->timestamp('enviado_em')->nullable();
            $table->timestamps();

            $table->unique(
                ['empresa_id', 'venda_id', 'numero_destino', 'tipo'],
                'uniq_wa_doc_nfe_envio'
            );
            $table->index(
                ['empresa_id', 'venda_id'],
                'whatsapp_documento_envios_empresa_id_venda_id_index'
            );
            $table->index('enviado_em', 'whatsapp_documento_envios_enviado_em_index');
        });
    }

    public function down(): void
    {
        // Rollback conservador: nenhum DROP e nenhuma remoção de dados ou de campos.
        // Os objetos acrescentados permanecem disponíveis após migrate:rollback.
    }
};
