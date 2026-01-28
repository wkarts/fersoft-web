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
        Schema::create('item_cotacaos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cotacao_id')->index('item_cotacaos_cotacao_id_foreign');
            $table->unsignedInteger('produto_id')->index('item_cotacaos_produto_id_foreign');
            $table->decimal('valor_unitario', 10);
            $table->decimal('valor', 10);
            $table->decimal('quantidade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_cotacaos');
    }
};
