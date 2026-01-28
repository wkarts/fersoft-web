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
        Schema::create('conta_recebers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('conta_recebers_empresa_id_foreign');
            $table->unsignedInteger('venda_id')->nullable()->index('conta_recebers_venda_id_foreign');
            $table->unsignedInteger('cliente_id')->nullable()->index('conta_recebers_cliente_id_foreign');
            $table->unsignedInteger('categoria_id')->index('conta_recebers_categoria_id_foreign');
            $table->string('referencia');
            $table->decimal('valor_integral', 16, 7);
            $table->decimal('valor_recebido', 16, 7)->default(0);
            $table->timestamp('date_register')->useCurrent();
            $table->date('data_vencimento');
            $table->timestamp('data_recebimento')->nullable();
            $table->boolean('status')->default(false);
            $table->decimal('juros', 16, 7)->default(0);
            $table->decimal('multa', 16, 7)->default(0);
            $table->unsignedInteger('venda_caixa_id')->nullable()->index('conta_recebers_venda_caixa_id_foreign');
            $table->string('observacao', 100);
            $table->string('tipo_pagamento', 30);
            $table->unsignedInteger('filial_id')->nullable()->index('conta_recebers_filial_id_foreign');
            $table->boolean('entrada')->default(false);
            $table->boolean('estorno')->default(false);
            $table->string('motivo_estorno', 100)->nullable();
            $table->integer('numero_nota_fiscal')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conta_recebers');
    }
};
