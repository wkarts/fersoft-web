<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequisicoesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('requisicoes')) {
            Schema::create('requisicoes', function (Blueprint $table) {
                $table->increments('id');

                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('usuario_id');
                $table->unsignedInteger('filial_id');

                $table->unsignedInteger('funcionario_id');
                $table->unsignedInteger('responsavel_id');

                $table->enum('unidade', ['Matriz', 'Filial']);
                $table->timestamp('data_requisicao')->useCurrent();
                $table->text('observacao')->nullable();
                $table->enum('status', ['Aberto', 'Finalizado'])->default('Aberto');

                $table->timestamps();

                $table->index('empresa_id', 'idx_requisicoes_empresa_id');
                $table->index('usuario_id', 'idx_requisicoes_usuario_id');
                $table->index('filial_id', 'idx_requisicoes_filial_id');
                $table->index('funcionario_id', 'idx_requisicoes_funcionario_id');
                $table->index('responsavel_id', 'idx_requisicoes_responsavel_id');

                $table->foreign('empresa_id', 'requisicoes_empresa_id_foreign')
                    ->references('id')->on('empresas')->onDelete('cascade');

                $table->foreign('usuario_id', 'requisicoes_usuario_id_foreign')
                    ->references('id')->on('usuarios')->onDelete('cascade');

                $table->foreign('filial_id', 'requisicoes_filial_id_foreign')
                    ->references('id')->on('filials')->onDelete('cascade');

                $table->foreign('funcionario_id', 'requisicoes_funcionario_id_foreign')
                    ->references('id')->on('funcionarios')->onDelete('cascade');

                $table->foreign('responsavel_id', 'requisicoes_responsavel_id_foreign')
                    ->references('id')->on('usuarios')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('requisicoes');
    }
}
