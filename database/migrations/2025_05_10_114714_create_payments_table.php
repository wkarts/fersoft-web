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
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('payments_empresa_id_foreign');
            $table->unsignedInteger('plano_id')->index('payments_plano_id_foreign');
            $table->decimal('valor', 10);
            $table->string('transacao_id', 100);
            $table->string('forma_pagamento', 10);
            $table->string('status', 15);
            $table->string('status_detalhe', 100);
            $table->string('descricao', 200);
            $table->text('link_boleto');
            $table->text('qr_code_base64');
            $table->text('qr_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
