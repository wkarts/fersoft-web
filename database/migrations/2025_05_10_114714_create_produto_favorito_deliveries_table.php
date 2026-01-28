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
        Schema::create('produto_favorito_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produto_id')->index('produto_favorito_deliveries_produto_id_foreign');
            $table->unsignedInteger('cliente_id')->index('produto_favorito_deliveries_cliente_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produto_favorito_deliveries');
    }
};
