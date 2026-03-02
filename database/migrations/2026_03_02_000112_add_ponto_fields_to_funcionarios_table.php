<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('funcionarios')) {
            Schema::table('funcionarios', function (Blueprint $table) {
                if (!Schema::hasColumn('funcionarios', 'matricula')) {
                    $table->string('matricula', 60)->nullable()->after('numero_registro');
                }
                if (!Schema::hasColumn('funcionarios', 'pis')) {
                    $table->string('pis', 20)->nullable()->after('matricula');
                }
                if (!Schema::hasColumn('funcionarios', 'data_demissao')) {
                    $table->date('data_demissao')->nullable()->after('data_admissao');
                }
                if (!Schema::hasColumn('funcionarios', 'jornada_padrao_id')) {
                    $table->unsignedInteger('jornada_padrao_id')->nullable()->after('pis');
                }
                if (!Schema::hasColumn('funcionarios', 'escala_padrao_id')) {
                    $table->unsignedInteger('escala_padrao_id')->nullable()->after('jornada_padrao_id');
                }
                if (!Schema::hasColumn('funcionarios', 'gestor_id')) {
                    $table->unsignedInteger('gestor_id')->nullable()->after('escala_padrao_id');
                }
                if (!Schema::hasColumn('funcionarios', 'centro_custo_id')) {
                    $table->unsignedInteger('centro_custo_id')->nullable()->after('gestor_id');
                }
                if (!Schema::hasColumn('funcionarios', 'ativo_ponto_mobile')) {
                    $table->boolean('ativo_ponto_mobile')->default(false)->after('centro_custo_id');
                }
                if (!Schema::hasColumn('funcionarios', 'codigo_relogio')) {
                    $table->string('codigo_relogio', 30)->nullable()->after('ativo_ponto_mobile');
                }
                if (!Schema::hasColumn('funcionarios', 'observacao_ponto')) {
                    $table->text('observacao_ponto')->nullable()->after('codigo_relogio');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('funcionarios')) {
            Schema::table('funcionarios', function (Blueprint $table) {
                $columns = [
                    'matricula', 'pis', 'data_demissao', 'jornada_padrao_id', 'escala_padrao_id',
                    'gestor_id', 'centro_custo_id', 'ativo_ponto_mobile', 'codigo_relogio', 'observacao_ponto'
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('funcionarios', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
