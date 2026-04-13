<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MergeManutencoesTableWithoutDataLoss extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('manutencoes')) {
            return;
        }

        Schema::table('manutencoes', function (Blueprint $table) {
            if (!Schema::hasColumn('manutencoes', 'tipo_execucao')) {
                $table->enum('tipo_execucao', ['Interno', 'Externo'])
                    ->default('Interno')
                    ->after('veiculo_id');
            }

            if (!Schema::hasColumn('manutencoes', 'nome_oficina_externa')) {
                $table->string('nome_oficina_externa', 255)
                    ->nullable()
                    ->after('tipo_execucao');
            }

            if (!Schema::hasColumn('manutencoes', 'km_registro')) {
                $table->decimal('km_registro', 10, 2)
                    ->nullable()
                    ->after('data_manutencao');
            }

            if (!Schema::hasColumn('manutencoes', 'local')) {
                $table->enum('local', ['interna', 'externa'])
                    ->default('externa')
                    ->after('deleted_at');
            }
        });

        // Índices auxiliares apenas para os novos campos
        Schema::table('manutencoes', function (Blueprint $table) {
            try {
                $table->index('tipo_execucao', 'idx_manutencoes_tipo_execucao');
            } catch (\Throwable $e) {
            }

            try {
                $table->index('local', 'idx_manutencoes_local');
            } catch (\Throwable $e) {
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('manutencoes')) {
            return;
        }

        Schema::table('manutencoes', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_manutencoes_local');
            } catch (\Throwable $e) {
            }

            try {
                $table->dropIndex('idx_manutencoes_tipo_execucao');
            } catch (\Throwable $e) {
            }

            $columnsToDrop = [];

            if (Schema::hasColumn('manutencoes', 'local')) {
                $columnsToDrop[] = 'local';
            }

            if (Schema::hasColumn('manutencoes', 'km_registro')) {
                $columnsToDrop[] = 'km_registro';
            }

            if (Schema::hasColumn('manutencoes', 'nome_oficina_externa')) {
                $columnsToDrop[] = 'nome_oficina_externa';
            }

            if (Schema::hasColumn('manutencoes', 'tipo_execucao')) {
                $columnsToDrop[] = 'tipo_execucao';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
}
