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
        Schema::create('venda_balcaos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('venda_balcaos_empresa_id_foreign');
            $table->string('codigo_venda', 8);
            $table->integer('numero_sequencial');
            $table->unsignedInteger('cliente_id')->nullable()->index('venda_balcaos_cliente_id_foreign');
            $table->string('cliente_nome', 50)->nullable();
            $table->unsignedInteger('usuario_id')->index('venda_balcaos_usuario_id_foreign');
            $table->unsignedInteger('transportadora_id')->nullable()->index('venda_balcaos_transportadora_id_foreign');
            $table->decimal('valor_total', 16, 7);
            $table->decimal('desconto', 10);
            $table->decimal('acrescimo', 10);
            $table->string('forma_pagamento', 20);
            $table->string('tipo_pagamento', 2);
            $table->string('observacao');
            $table->boolean('estado')->default(false);
            $table->string('bandeira_cartao', 2)->default('99');
            $table->string('cnpj_cartao', 18)->default('');
            $table->string('cAut_cartao', 20)->default('');
            $table->string('descricao_pag_outros', 80)->default('');
            $table->unsignedInteger('filial_id')->nullable()->index('venda_balcaos_filial_id_foreign');
            $table->integer('venda_id')->nullable();
            $table->enum('tipo_venda', ['nfe', 'nfce']);
            $table->string('placa', 9);
            $table->string('uf', 2);
            $table->decimal('valor', 10);
            $table->integer('tipo');
            $table->integer('quantidade_volumes');
            $table->string('numeracao_volumes', 20);
            $table->string('especie', 20);
            $table->decimal('peso_liquido', 8, 3);
            $table->decimal('peso_bruto', 8, 3);
            $table->integer('vendedor_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venda_balcaos');
    }
};
