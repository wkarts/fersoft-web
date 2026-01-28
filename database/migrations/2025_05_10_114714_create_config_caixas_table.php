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
        Schema::create('config_caixas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('finalizar', 15);
            $table->string('reiniciar', 15);
            $table->string('editar_desconto', 15);
            $table->string('editar_acrescimo', 15);
            $table->string('editar_observacao', 15);
            $table->string('setar_valor_recebido', 15);
            $table->string('forma_pagamento_dinheiro', 15);
            $table->string('forma_pagamento_debito', 15);
            $table->string('forma_pagamento_credito', 15);
            $table->string('forma_pagamento_pix', 15);
            $table->string('setar_leitor', 15);
            $table->string('setar_quantidade', 15);
            $table->string('finalizar_fiscal', 15);
            $table->string('finalizar_nao_fiscal', 15);
            $table->boolean('botao_nao_fiscal')->default(true);
            $table->boolean('valor_recebido_automatico');
            $table->boolean('balanca_valor_peso');
            $table->integer('balanca_digito_verificador');
            $table->unsignedInteger('usuario_id')->index('config_caixas_usuario_id_foreign');
            $table->string('mercadopago_public_key', 120);
            $table->string('mercadopago_access_token', 120);
            $table->string('tipos_pagamento')->default('[]');
            $table->string('tipo_pagamento_padrao', 15)->default('');
            $table->integer('impressora_modelo')->default(80);
            $table->enum('impressao_pre_venda', ['80', 'a4'])->default('80');
            $table->integer('cupom_modelo')->default(2);
            $table->integer('modelo_pdv')->default(2);
            $table->boolean('exibe_produtos')->default(false);
            $table->boolean('exibe_modal_cartoes')->default(false);
            $table->boolean('imprimir_ticket_troca')->default(false);
            $table->string('mensagem_padrao_cupom')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_caixas');
    }
};
