<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pedido_ifoods', function (Blueprint $table) {
            $table->increments('id');
            $table->string('status', 10);
            $table->string('pedido_id', 50);
            $table->string('data_pedido', 30);
            $table->unsignedInteger('empresa_id')->index('pedido_ifoods_empresa_id_foreign');
            $table->string('tipo_pedido', 40)->nullable();
            $table->string('endereco')->nullable();
            $table->string('bairro', 50)->nullable();
            $table->string('cep', 10)->nullable();
            $table->string('nome_cliente', 100)->nullable();
            $table->string('id_cliente', 100)->nullable();
            $table->string('telefone_cliente', 100)->nullable();
            $table->decimal('valor_produtos', 10)->nullable();
            $table->decimal('valor_entrega', 10)->nullable();
            $table->decimal('valor_total', 10)->nullable();
            $table->decimal('taxas_adicionais', 10)->nullable();
            $table->string('cpf_na_nota', 20)->nullable();
            $table->boolean('status_leitura')->default(false);
            $table->boolean('status_driver')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_ifoods');
    }
};
