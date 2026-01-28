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
        Schema::create('venda_caixas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('venda_caixas_empresa_id_foreign');
            $table->unsignedInteger('cliente_id')->nullable()->index('venda_caixas_cliente_id_foreign');
            $table->unsignedInteger('usuario_id')->index('venda_caixas_usuario_id_foreign');
            $table->unsignedInteger('natureza_id')->index('venda_caixas_natureza_id_foreign');
            $table->timestamp('data_registro')->useCurrent();
            $table->decimal('valor_total', 16, 7);
            $table->decimal('dinheiro_recebido', 10);
            $table->decimal('troco', 10);
            $table->decimal('desconto', 10);
            $table->decimal('valor_cashback', 10);
            $table->decimal('acrescimo', 10);
            $table->string('forma_pagamento', 20);
            $table->string('tipo_pagamento', 2);
            $table->string('estado', 20);
            $table->integer('NFcNumero')->default(0);
            $table->string('chave', 48);
            $table->string('path_xml', 48);
            $table->string('nome', 50);
            $table->string('cpf', 18);
            $table->string('observacao', 150);
            $table->integer('pedido_delivery_id');
            $table->integer('pedido_ifood_id')->nullable();
            $table->string('tipo_pagamento_1', 20)->default('');
            $table->decimal('valor_pagamento_1', 10)->default(0);
            $table->string('tipo_pagamento_2', 20)->default('');
            $table->decimal('valor_pagamento_2', 10)->default(0);
            $table->string('tipo_pagamento_3', 20)->default('');
            $table->decimal('valor_pagamento_3', 10)->default(0);
            $table->text('qr_code_base64');
            $table->string('bandeira_cartao', 2)->default('99');
            $table->string('cnpj_cartao', 18)->default('');
            $table->string('cAut_cartao', 20)->default('');
            $table->string('descricao_pag_outros', 80)->default('');
            $table->boolean('rascunho')->default(false);
            $table->boolean('consignado')->default(false);
            $table->boolean('pdv_java')->default(false);
            $table->boolean('retorno_estoque')->default(false);
            $table->boolean('troca')->default(false);
            $table->integer('prevenda_nivel')->default(0);
            $table->decimal('credito_troca', 10)->default(0);
            $table->unsignedInteger('filial_id')->nullable()->index('venda_caixas_filial_id_foreign');
            $table->integer('numero_sequencial');
            $table->boolean('contigencia')->default(false);
            $table->boolean('reenvio_contigencia')->default(false);
            $table->timestamp('data_emissao')->nullable();
            $table->text('signed_xml')->nullable();
            $table->text('recibo')->nullable();
            $table->integer('vendedor_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venda_caixas');
    }
};
