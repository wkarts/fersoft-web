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
        Schema::create('troca_venda_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('troca_id')->index('troca_venda_items_troca_id_foreign');
            $table->unsignedInteger('produto_id')->index('troca_venda_items_produto_id_foreign');
            $table->decimal('valor', 16, 7);
            $table->decimal('quantidade', 10, 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('troca_venda_items');
    }
};
