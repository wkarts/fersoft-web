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
        Schema::create('item_venda_balcaos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venda_balcao_id')->index('item_venda_balcaos_venda_balcao_id_foreign');
            $table->unsignedInteger('produto_id')->index('item_venda_balcaos_produto_id_foreign');
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
        Schema::dropIfExists('item_venda_balcaos');
    }
};
