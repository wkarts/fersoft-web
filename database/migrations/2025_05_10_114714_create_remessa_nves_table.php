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
        Schema::create('remessa_nves', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('remessa_nves_empresa_id_foreign');
            $table->unsignedInteger('filial_id')->nullable()->index('remessa_nves_filial_id_foreign');
            $table->unsignedInteger('cliente_id')->index('remessa_nves_cliente_id_foreign');
            $table->unsignedInteger('usuario_id')->index('remessa_nves_usuario_id_foreign');
            $table->unsignedInteger('natureza_id')->index('remessa_nves_natureza_id_foreign');
            $table->unsignedInteger('transportadora_id')->nullable()->index('remessa_nves_transportadora_id_foreign');
            $table->integer('numero_sequencial');
            $table->date('data_entrega')->nullable();
            $table->decimal('valor_total', 16, 7);
            $table->decimal('desconto', 10);
            $table->decimal('acrescimo', 10);
            $table->string('forma_pagamento', 2);
            $table->text('observacao');
            $table->enum('estado', ['novo', 'rejeitado', 'cancelado', 'aprovado']);
            $table->integer('sequencia_cce');
            $table->integer('nSerie')->default(0);
            $table->integer('numero_nfe')->default(0);
            $table->string('chave', 48);
            $table->string('descricao_pag_outros', 80)->nullable();
            $table->timestamp('data_emissao')->nullable();
            $table->boolean('baixa_estoque');
            $table->boolean('gerar_conta_receber');
            $table->enum('tipo_nfe', ['normal', 'remessa', 'estorno']);
            $table->string('placa', 9)->nullable();
            $table->string('uf', 2)->nullable();
            $table->decimal('valor_frete', 10)->nullable();
            $table->integer('tipo_frete')->nullable();
            $table->integer('qtd_volumes')->nullable();
            $table->string('numeracao_volumes', 20)->nullable();
            $table->string('especie', 20)->nullable();
            $table->decimal('peso_liquido', 8, 3)->nullable();
            $table->decimal('peso_bruto', 8, 3)->nullable();
            $table->date('data_retroativa')->nullable();
            $table->date('data_saida')->nullable();
            $table->integer('venda_caixa_id')->nullable();
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
        Schema::dropIfExists('remessa_nves');
    }
};
