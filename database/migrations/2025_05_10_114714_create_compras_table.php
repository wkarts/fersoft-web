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
        Schema::create('compras', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('compras_empresa_id_foreign');
            $table->unsignedInteger('fornecedor_id')->index('compras_fornecedor_id_foreign');
            $table->unsignedInteger('usuario_id')->index('compras_usuario_id_foreign');
            $table->integer('categoria_conta_id')->nullable();
            $table->string('observacao');
            $table->string('xml_path', 48);
            $table->string('chave', 44);
            $table->string('nf', 20);
            $table->integer('numero_emissao');
            $table->string('estado', 10);
            $table->string('lote', 20)->nullable();
            $table->decimal('valor', 16, 7);
            $table->decimal('desconto', 10);
            $table->decimal('acrescimo', 10);
            $table->integer('sequencia_cce');
            $table->string('placa', 9);
            $table->string('uf', 2);
            $table->decimal('valor_frete', 10);
            $table->integer('tipo');
            $table->integer('qtdVolumes');
            $table->string('numeracaoVolumes', 20);
            $table->string('especie', 20);
            $table->decimal('peso_liquido', 8, 3);
            $table->decimal('peso_bruto', 8, 3);
            $table->unsignedInteger('transportadora_id')->nullable()->index('compras_transportadora_id_foreign');
            $table->string('tipo_pagamento', 2)->default('');
            $table->integer('natureza_id')->default(0);
            $table->timestamp('data_emissao')->nullable();
            $table->unsignedInteger('filial_id')->nullable()->index('compras_filial_id_foreign');
            $table->integer('numero_sequencial');
            $table->date('data_retroativa')->nullable();
            $table->date('data_saida')->nullable();
            $table->boolean('xml_importado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
