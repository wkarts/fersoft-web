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
        Schema::create('agendamentos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('funcionario_id')->index('agendamentos_funcionario_id_foreign');
            $table->unsignedInteger('cliente_id')->nullable()->index('agendamentos_cliente_id_foreign');
            $table->unsignedInteger('empresa_id')->index('agendamentos_empresa_id_foreign');
            $table->date('data');
            $table->string('observacao', 150);
            $table->time('inicio');
            $table->time('termino');
            $table->decimal('total', 10);
            $table->decimal('desconto', 10);
            $table->decimal('acrescimo', 10);
            $table->decimal('valor_comissao', 10)->default(0);
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendamentos');
    }
};
