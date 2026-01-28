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
        Schema::create('item_servico_venda_caixas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venda_caixa_id')->index('item_servico_venda_caixas_venda_caixa_id_foreign');
            $table->unsignedInteger('servico_id')->index('item_servico_venda_caixas_servico_id_foreign');
            $table->decimal('quantidade', 16, 7);
            $table->decimal('valor', 16, 7);
            $table->decimal('sub_total', 16, 7);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_servico_venda_caixas');
    }
};
