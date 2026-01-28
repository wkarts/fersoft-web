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
        Schema::create('vendas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('vendas_empresa_id_foreign');
            $table->integer('numero_sequencial');
            $table->unsignedInteger('cliente_id')->index('vendas_cliente_id_foreign');
            $table->unsignedInteger('usuario_id')->index('vendas_usuario_id_foreign');
            $table->unsignedInteger('natureza_id')->index('vendas_natureza_id_foreign');
            $table->unsignedInteger('frete_id')->nullable()->index('vendas_frete_id_foreign');
            $table->unsignedInteger('transportadora_id')->nullable()->index('vendas_transportadora_id_foreign');
            $table->timestamp('data_registro')->useCurrent();
            $table->date('data_entrega')->nullable();
            $table->decimal('valor_total', 16, 7);
            $table->decimal('desconto', 10);
            $table->decimal('acrescimo', 10);
            $table->string('forma_pagamento', 20);
            $table->string('tipo_pagamento', 2);
            $table->text('observacao');
            $table->string('estado', 20);
            $table->integer('sequencia_cce');
            $table->integer('NfNumero')->default(0);
            $table->integer('nSerie')->default(0);
            $table->string('chave', 48);
            $table->string('path_xml', 51);
            $table->integer('pedido_ecommerce_id')->default(0);
            $table->integer('pedido_nuvemshop_id')->default(0);
            $table->string('bandeira_cartao', 2)->default('99');
            $table->string('cnpj_cartao', 18)->default('');
            $table->string('cAut_cartao', 20)->default('');
            $table->string('descricao_pag_outros', 80)->default('');
            $table->timestamp('data_emissao')->nullable();
            $table->boolean('troca')->default(false);
            $table->decimal('credito_troca', 10)->default(0);
            $table->date('data_retroativa')->nullable();
            $table->date('data_saida')->nullable();
            $table->integer('vendedor_id')->nullable();
            $table->unsignedInteger('filial_id')->nullable()->index('vendas_filial_id_foreign');
            $table->boolean('contigencia')->default(false);
            $table->boolean('reenvio_contigencia')->default(false);
            $table->text('signed_xml')->nullable();
            $table->string('recibo', 30)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendas');
    }
};
