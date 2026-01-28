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
        Schema::create('pedido_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('pedido_deliveries_empresa_id_foreign');
            $table->unsignedInteger('cliente_id')->index('pedido_deliveries_cliente_id_foreign');
            $table->timestamp('data_registro')->useCurrent();
            $table->decimal('valor_total', 10);
            $table->decimal('troco_para', 10);
            $table->string('forma_pagamento', 20);
            $table->string('observacao', 50);
            $table->string('telefone', 15);
            $table->enum('estado', ['novo', 'aprovado', 'cancelado', 'finalizado']);
            $table->string('motivoEstado', 50);
            $table->unsignedInteger('endereco_id')->nullable()->index('pedido_deliveries_endereco_id_foreign');
            $table->unsignedInteger('cupom_id')->nullable()->index('pedido_deliveries_cupom_id_foreign');
            $table->decimal('desconto', 10);
            $table->decimal('valor_entrega', 10);
            $table->boolean('app');
            $table->text('qr_code_base64');
            $table->text('qr_code');
            $table->string('transacao_id', 50)->default('');
            $table->string('status_pagamento', 100)->default('');
            $table->boolean('pedido_lido')->default(false);
            $table->string('horario_cricao', 5);
            $table->string('horario_leitura', 5);
            $table->string('horario_entrega', 5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_deliveries');
    }
};
