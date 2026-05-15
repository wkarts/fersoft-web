<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAbastecimentosTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('abastecimentos')) {
            Schema::create('abastecimentos', function (Blueprint $table) {
                $table->increments('id');

                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('usuario_id')->nullable();
                $table->unsignedInteger('filial_id')->nullable();

                $table->unsignedInteger('veiculo_id');
                $table->unsignedInteger('fornecedor_id')->nullable();
                $table->unsignedInteger('compra_id')->nullable();

                $table->date('data');
                $table->integer('quilometragem');
                $table->decimal('litros', 10, 2);
                $table->decimal('valor_litro', 10, 2);
                $table->decimal('valor_total', 10, 2);
                $table->enum('tipo_combustivel', ['Gasolina', 'Etanol', 'Diesel', 'GNV'])->default('Diesel');

                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->index('empresa_id', 'idx_abastecimentos_empresa_id');
                $table->index('usuario_id', 'idx_abastecimentos_usuario_id');
                $table->index('filial_id', 'idx_abastecimentos_filial_id');
                $table->index('veiculo_id', 'abastecimentos_veiculo_id_foreign');
                $table->index('fornecedor_id', 'idx_abastecimentos_fornecedor_id');
                $table->index('compra_id', 'idx_abastecimentos_compra_id');

                $table->foreign('empresa_id', 'abastecimentos_empresa_id_foreign')
                    ->references('id')->on('empresas')->onDelete('cascade');

                $table->foreign('usuario_id', 'abastecimentos_usuario_id_foreign')
                    ->references('id')->on('usuarios')->onDelete('cascade');

                $table->foreign('filial_id', 'abastecimentos_filial_id_foreign')
                    ->references('id')->on('filials')->onDelete('cascade');

                $table->foreign('veiculo_id', 'abastecimentos_veiculo_fk')
                    ->references('id')->on('veiculos')->onDelete('cascade');

                $table->foreign('fornecedor_id', 'abastecimentos_fornecedor_fk')
                    ->references('id')->on('fornecedors')->onDelete('set null');

                $table->foreign('compra_id', 'abastecimentos_compra_fk')
                    ->references('id')->on('compras')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('abastecimentos');
    }
}
