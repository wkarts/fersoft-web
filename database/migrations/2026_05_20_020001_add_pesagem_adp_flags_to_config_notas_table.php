<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('config_notas', function (Blueprint $table) {
            if (!Schema::hasColumn('config_notas', 'pesagem_habilitar_preview_cameras')) {
                $table->boolean('pesagem_habilitar_preview_cameras')->default(true)->after('conectar_automaticamente_balanca_ao_selecionar');
            }
            if (!Schema::hasColumn('config_notas', 'pesagem_exigir_imagem_quando_balanca_tem_camera')) {
                $table->boolean('pesagem_exigir_imagem_quando_balanca_tem_camera')->default(false)->after('pesagem_habilitar_preview_cameras');
            }
            if (!Schema::hasColumn('config_notas', 'pesagem_auto_concluir_ticket')) {
                $table->boolean('pesagem_auto_concluir_ticket')->default(false)->after('pesagem_exigir_imagem_quando_balanca_tem_camera');
            }
            if (!Schema::hasColumn('config_notas', 'pesagem_bloquear_edicao_ticket_concluido')) {
                $table->boolean('pesagem_bloquear_edicao_ticket_concluido')->default(false)->after('pesagem_auto_concluir_ticket');
            }
            if (!Schema::hasColumn('config_notas', 'pesagem_imprimir_imagens_a4')) {
                $table->boolean('pesagem_imprimir_imagens_a4')->default(true)->after('pesagem_bloquear_edicao_ticket_concluido');
            }
            if (!Schema::hasColumn('config_notas', 'pesagem_imprimir_imagens_80mm')) {
                $table->boolean('pesagem_imprimir_imagens_80mm')->default(false)->after('pesagem_imprimir_imagens_a4');
            }
            if (!Schema::hasColumn('config_notas', 'pesagem_email_destino')) {
                $table->string('pesagem_email_destino', 255)->nullable()->after('pesagem_imprimir_imagens_80mm');
            }
            if (!Schema::hasColumn('config_notas', 'pesagem_whatsapp_destino')) {
                $table->string('pesagem_whatsapp_destino', 30)->nullable()->after('pesagem_email_destino');
            }
            if (!Schema::hasColumn('config_notas', 'pesagem_enviar_email_ao_concluir')) {
                $table->boolean('pesagem_enviar_email_ao_concluir')->default(false)->after('pesagem_whatsapp_destino');
            }
            if (!Schema::hasColumn('config_notas', 'pesagem_enviar_whatsapp_ao_concluir')) {
                $table->boolean('pesagem_enviar_whatsapp_ao_concluir')->default(false)->after('pesagem_enviar_email_ao_concluir');
            }
            if (!Schema::hasColumn('config_notas', 'pesagem_enviar_imagens_notificacao')) {
                $table->boolean('pesagem_enviar_imagens_notificacao')->default(true)->after('pesagem_enviar_whatsapp_ao_concluir');
            }
        });
    }

    public function down(): void
    {
        Schema::table('config_notas', function (Blueprint $table) {
            foreach ([
                'pesagem_enviar_imagens_notificacao',
                'pesagem_enviar_whatsapp_ao_concluir',
                'pesagem_enviar_email_ao_concluir',
                'pesagem_whatsapp_destino',
                'pesagem_email_destino',
                'pesagem_imprimir_imagens_80mm',
                'pesagem_imprimir_imagens_a4',
                'pesagem_bloquear_edicao_ticket_concluido',
                'pesagem_auto_concluir_ticket',
                'pesagem_exigir_imagem_quando_balanca_tem_camera',
                'pesagem_habilitar_preview_cameras',
            ] as $column) {
                if (Schema::hasColumn('config_notas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
