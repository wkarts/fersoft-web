<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MergeFuncionariosTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('funcionarios')) {
            return;
        }

        Schema::table('funcionarios', function (Blueprint $table) {
            // Campos existentes apenas na estrutura Development
            // Mantidos exatamente com os mesmos nomes e tipos
            // Sem remover, renomear ou substituir nada

            if (!Schema::hasColumn('funcionarios', 'funcao_id')) {
                $table->integer('funcao_id')->nullable()->after('nome');
            }

            if (!Schema::hasColumn('funcionarios', 'filial_id')) {
                $table->integer('filial_id')->nullable()->after('funcao_id');
            }

            if (!Schema::hasColumn('funcionarios', 'funcao')) {
                $table->string('funcao', 150)->nullable()->after('filial_id');
            }

            if (!Schema::hasColumn('funcionarios', 'unidade_tipo')) {
                $table->boolean('unidade_tipo')
                    ->nullable()
                    ->default(1)
                    ->comment('1: Matriz, 2: Filial')
                    ->after('funcao');
            }

            if (!Schema::hasColumn('funcionarios', 'unidade')) {
                $table->enum('unidade', ['Matriz', 'Filial'])
                    ->nullable()
                    ->default('Matriz')
                    ->after('unidade_tipo');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('funcionarios')) {
            return;
        }

        Schema::table('funcionarios', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('funcionarios', 'unidade')) {
                $columnsToDrop[] = 'unidade';
            }

            if (Schema::hasColumn('funcionarios', 'unidade_tipo')) {
                $columnsToDrop[] = 'unidade_tipo';
            }

            if (Schema::hasColumn('funcionarios', 'funcao')) {
                $columnsToDrop[] = 'funcao';
            }

            if (Schema::hasColumn('funcionarios', 'filial_id')) {
                $columnsToDrop[] = 'filial_id';
            }

            if (Schema::hasColumn('funcionarios', 'funcao_id')) {
                $columnsToDrop[] = 'funcao_id';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
}
