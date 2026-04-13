<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequisicaoItensTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('requisicao_itens')) {
            Schema::create('requisicao_itens', function (Blueprint $table) {
                $table->increments('id');

                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('usuario_id');
                $table->unsignedInteger('filial_id');

                $table->unsignedInteger('requisicao_id');
                $table->unsignedInteger('produto_id');

                $table->decimal('quantidade', 10, 3);
                $table->string('uso', 2)->nullable();
                $table->string('motivo', 2)->nullable();

                $table->timestamps();

                $table->index('empresa_id', 'idx_requisicao_itens_empresa_id');
                $table->index('usuario_id', 'idx_requisicao_itens_usuario_id');
                $table->index('filial_id', 'idx_requisicao_itens_filial_id');
                $table->index('requisicao_id', 'idx_requisicao_itens_requisicao_id');
                $table->index('produto_id', 'idx_requisicao_itens_produto_id');

                $table->foreign('empresa_id', 'requisicao_itens_empresa_id_foreign')
                    ->references('id')->on('empresas')->onDelete('cascade');

                $table->foreign('usuario_id', 'requisicao_itens_usuario_id_foreign')
                    ->references('id')->on('usuarios')->onDelete('cascade');

                $table->foreign('filial_id', 'requisicao_itens_filial_id_foreign')
                    ->references('id')->on('filials')->onDelete('cascade');

                $table->foreign('produto_id', 'requisicao_itens_produto_id_foreign')
                    ->references('id')->on('produtos')->onDelete('cascade');

                $table->foreign('requisicao_id', 'requisicao_itens_requisicao_id_foreign')
                    ->references('id')->on('requisicoes')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('requisicao_itens');
    }
}
