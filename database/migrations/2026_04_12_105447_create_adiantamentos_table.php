<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdiantamentosTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('adiantamentos')) {
            Schema::create('adiantamentos', function (Blueprint $table) {
                $table->increments('id');

                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('usuario_id');
                $table->unsignedInteger('filial_id');

                $table->unsignedInteger('cliente_id')->nullable();
                $table->unsignedInteger('fornecedor_id')->nullable();
                $table->decimal('valor_total', 16, 2);
                $table->decimal('valor_utilizado', 16, 2)->default(0.00);
                $table->date('data');
                $table->enum('status', ['aberto', 'finalizado', 'cancelado'])->default('aberto');
                $table->string('descricao', 255)->nullable();
                $table->unsignedInteger('item_conta_empresa_id')->nullable();

                $table->timestamps();

                $table->index('empresa_id', 'idx_adiantamentos_empresa_id');
                $table->index('usuario_id', 'idx_adiantamentos_usuario_id');
                $table->index('filial_id', 'idx_adiantamentos_filial_id');
                $table->index('cliente_id', 'idx_adiantamentos_cliente_id');
                $table->index('fornecedor_id', 'idx_adiantamentos_fornecedor_id');
                $table->index('item_conta_empresa_id', 'idx_adiantamentos_item_conta_empresa_id');

                $table->foreign('empresa_id', 'adiantamentos_empresa_id_foreign')
                    ->references('id')->on('empresas')->onDelete('cascade');

                $table->foreign('usuario_id', 'adiantamentos_usuario_id_foreign')
                    ->references('id')->on('usuarios')->onDelete('cascade');

                $table->foreign('filial_id', 'adiantamentos_filial_id_foreign')
                    ->references('id')->on('filials')->onDelete('cascade');

                $table->foreign('cliente_id', 'adiantamentos_cliente_id_foreign')
                    ->references('id')->on('clientes')->onDelete('set null');

                $table->foreign('fornecedor_id', 'adiantamentos_fornecedor_id_foreign')
                    ->references('id')->on('fornecedors')->onDelete('set null');

                $table->foreign('item_conta_empresa_id', 'adiantamentos_item_conta_empresa_id_foreign')
                    ->references('id')->on('item_conta_empresas')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('adiantamentos');
    }
}
