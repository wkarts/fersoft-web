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
        Schema::create('curtida_produto_ecommerces', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produto_id')->index('curtida_produto_ecommerces_produto_id_foreign');
            $table->unsignedInteger('cliente_id')->nullable()->index('curtida_produto_ecommerces_cliente_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curtida_produto_ecommerces');
    }
};
