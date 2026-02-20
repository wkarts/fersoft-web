<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('config_notas')) {
            return;
        }

        Schema::table('config_notas', function (Blueprint $table) {
            if (!Schema::hasColumn('config_notas', 'conectar_automaticamente_balanca_padrao_usuario')) {
                $table->boolean('conectar_automaticamente_balanca_padrao_usuario')
                    ->default(false)
                    ->after('desbloquear_campo_peso_bag_ticket');
            }

            if (!Schema::hasColumn('config_notas', 'conectar_automaticamente_balanca_ao_selecionar')) {
                $table->boolean('conectar_automaticamente_balanca_ao_selecionar')
                    ->default(false)
                    ->after('conectar_automaticamente_balanca_padrao_usuario');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('config_notas')) {
            return;
        }

        Schema::table('config_notas', function (Blueprint $table) {
            if (Schema::hasColumn('config_notas', 'conectar_automaticamente_balanca_ao_selecionar')) {
                $table->dropColumn('conectar_automaticamente_balanca_ao_selecionar');
            }

            if (Schema::hasColumn('config_notas', 'conectar_automaticamente_balanca_padrao_usuario')) {
                $table->dropColumn('conectar_automaticamente_balanca_padrao_usuario');
            }
        });
    }
};
