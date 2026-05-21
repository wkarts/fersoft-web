<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('config_notas', function (Blueprint $table) {
            if (!Schema::hasColumn('config_notas', 'pesagem_email_destinos_json')) {
                $table->json('pesagem_email_destinos_json')->nullable()->after('pesagem_email_destino');
            }
            if (!Schema::hasColumn('config_notas', 'pesagem_whatsapp_destinos_json')) {
                $table->json('pesagem_whatsapp_destinos_json')->nullable()->after('pesagem_whatsapp_destino');
            }
            if (!Schema::hasColumn('config_notas', 'pesagem_snapshot_disk')) {
                $table->string('pesagem_snapshot_disk', 40)->nullable()->after('pesagem_enviar_imagens_notificacao');
            }
            if (!Schema::hasColumn('config_notas', 'pesagem_snapshot_base_path')) {
                $table->string('pesagem_snapshot_base_path', 255)->nullable()->after('pesagem_snapshot_disk');
            }
        });

        Schema::table('pesagem_ticket_imagens', function (Blueprint $table) {
            if (!Schema::hasColumn('pesagem_ticket_imagens', 'storage_disk')) {
                $table->string('storage_disk', 40)->nullable()->after('ativo');
            }
            if (!Schema::hasColumn('pesagem_ticket_imagens', 'storage_base_path')) {
                $table->string('storage_base_path', 255)->nullable()->after('storage_disk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pesagem_ticket_imagens', function (Blueprint $table) {
            foreach (['storage_base_path', 'storage_disk'] as $column) {
                if (Schema::hasColumn('pesagem_ticket_imagens', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('config_notas', function (Blueprint $table) {
            foreach ([
                'pesagem_snapshot_base_path',
                'pesagem_snapshot_disk',
                'pesagem_whatsapp_destinos_json',
                'pesagem_email_destinos_json',
            ] as $column) {
                if (Schema::hasColumn('config_notas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
