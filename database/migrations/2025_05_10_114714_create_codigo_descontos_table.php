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
        Schema::create('codigo_descontos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('codigo_descontos_empresa_id_foreign');
            $table->integer('codigo');
            $table->string('descricao', 50);
            $table->unsignedInteger('cliente_id')->nullable()->index('codigo_descontos_cliente_id_foreign');
            $table->string('tipo');
            $table->decimal('valor', 10, 4);
            $table->decimal('valor_minimo_pedido', 12, 4);
            $table->boolean('ativo');
            $table->boolean('push');
            $table->boolean('sms');
            $table->date('expiracao');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codigo_descontos');
    }
};
