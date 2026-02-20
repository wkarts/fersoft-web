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
            if (!Schema::hasColumn('config_notas', 'bloquear_pesagem_manual_balanca')) {
                $table->boolean('bloquear_pesagem_manual_balanca')
                    ->default(false)
                    ->after('busca_documento_automatico');
            }

            if (!Schema::hasColumn('config_notas', 'usar_valores_ticket_pesagem')) {
                $table->boolean('usar_valores_ticket_pesagem')
                    ->default(false)
                    ->after('bloquear_pesagem_manual_balanca');
            }

            if (!Schema::hasColumn('config_notas', 'exibir_valores_ticket_pesagem_grid')) {
                $table->boolean('exibir_valores_ticket_pesagem_grid')
                    ->default(false)
                    ->after('usar_valores_ticket_pesagem');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('config_notas')) {
            return;
        }

        Schema::table('config_notas', function (Blueprint $table) {
            foreach ([
                'bloquear_pesagem_manual_balanca',
                'usar_valores_ticket_pesagem',
                'exibir_valores_ticket_pesagem_grid',
            ] as $column) {
                if (Schema::hasColumn('config_notas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
