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
        Schema::create('produto_pizzas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produto_id')->nullable()->index('produto_pizzas_produto_id_foreign');
            $table->unsignedInteger('tamanho_id')->nullable()->index('produto_pizzas_tamanho_id_foreign');
            $table->decimal('valor', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produto_pizzas');
    }
};
