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
        Schema::create('comissao_vendas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('comissao_vendas_empresa_id_foreign');
            $table->unsignedInteger('funcionario_id')->nullable()->index('comissao_vendas_funcionario_id_foreign');
            $table->integer('venda_id');
            $table->string('tabela', 14);
            $table->decimal('valor', 10);
            $table->boolean('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comissao_vendas');
    }
};
