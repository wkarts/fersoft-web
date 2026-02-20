<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('config_notas')) {
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

        if (Schema::hasTable('usuarios')) {
            Schema::table('usuarios', function (Blueprint $table) {
                if (!Schema::hasColumn('usuarios', 'balanca_padrao_id')) {
                    $table->unsignedInteger('balanca_padrao_id')
                        ->nullable()
                        ->after('local_padrao')
                        ->index('usuarios_balanca_padrao_id_index');
                }
            });
        }

        if (Schema::hasTable('tickets_pesagem')) {
            Schema::table('tickets_pesagem', function (Blueprint $table) {
                if (!Schema::hasColumn('tickets_pesagem', 'balanca_config_id')) {
                    $table->unsignedInteger('balanca_config_id')
                        ->nullable()
                        ->after('motorista_id')
                        ->index('tickets_pesagem_balanca_config_id_index');
                }

                if (!Schema::hasColumn('tickets_pesagem', 'peso_origem')) {
                    $table->string('peso_origem', 30)
                        ->default('manual')
                        ->after('peso_bag');
                }

                if (!Schema::hasColumn('tickets_pesagem', 'valor_unitario')) {
                    $table->decimal('valor_unitario', 18, 6)
                        ->default(0)
                        ->after('peso_origem');
                }

                if (!Schema::hasColumn('tickets_pesagem', 'valor_total')) {
                    $table->decimal('valor_total', 18, 6)
                        ->default(0)
                        ->after('valor_unitario');
                }

                if (!Schema::hasColumn('tickets_pesagem', 'valor_origem')) {
                    $table->string('valor_origem', 30)
                        ->default('manual')
                        ->after('valor_total');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tickets_pesagem')) {
            Schema::table('tickets_pesagem', function (Blueprint $table) {
                foreach (['balanca_config_id', 'peso_origem', 'valor_unitario', 'valor_total', 'valor_origem'] as $column) {
                    if (Schema::hasColumn('tickets_pesagem', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('usuarios')) {
            Schema::table('usuarios', function (Blueprint $table) {
                if (Schema::hasColumn('usuarios', 'balanca_padrao_id')) {
                    $table->dropColumn('balanca_padrao_id');
                }
            });
        }

        if (Schema::hasTable('config_notas')) {
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
    }
};
