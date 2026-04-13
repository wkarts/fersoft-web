<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstoqueMensalFechamentosTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('estoque_mensal_fechamentos')) {
            Schema::create('estoque_mensal_fechamentos', function (Blueprint $table) {
                $table->increments('id');

                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('usuario_id');
                $table->unsignedInteger('filial_id');

                $table->unsignedInteger('produto_id');

                $table->decimal('quantidade', 16, 7);
                $table->decimal('valor_unitario_custo', 16, 7);
                $table->integer('mes');
                $table->integer('ano');

                $table->timestamps();

                $table->index('empresa_id', 'idx_estoque_mensal_empresa_id');
                $table->index('usuario_id', 'idx_estoque_mensal_usuario_id');
                $table->index('filial_id', 'idx_estoque_mensal_filial_id');
                $table->index('produto_id', 'idx_estoque_mensal_produto_id');
                $table->unique(['empresa_id', 'filial_id', 'produto_id', 'mes', 'ano'], 'uq_estoque_mensal_periodo');

                $table->foreign('empresa_id', 'estoque_mensal_empresa_id_foreign')
                    ->references('id')->on('empresas')->onDelete('cascade');

                $table->foreign('usuario_id', 'estoque_mensal_usuario_id_foreign')
                    ->references('id')->on('usuarios')->onDelete('cascade');

                $table->foreign('filial_id', 'estoque_mensal_filial_id_foreign')
                    ->references('id')->on('filials')->onDelete('cascade');

                $table->foreign('produto_id', 'estoque_mensal_produto_id_foreign')
                    ->references('id')->on('produtos')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('estoque_mensal_fechamentos');
    }
}
