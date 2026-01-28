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
        Schema::create('troca_venda_caixas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id')->index('troca_venda_caixas_empresa_id_foreign');
            $table->unsignedInteger('antiga_venda_caixas_id')->index('troca_venda_caixas_antiga_venda_caixas_id_foreign');
            $table->unsignedInteger('nova_venda_caixas_id')->index('troca_venda_caixas_nova_venda_caixas_id_foreign');
            $table->text('prod_removidos');
            $table->text('prod_adicionados');
            $table->text('observacao');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('troca_venda_caixas');
    }
};
