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
        Schema::create('cupom_ecommerce_utilizados', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cupom_id')->index('cupom_ecommerce_utilizados_cupom_id_foreign');
            $table->unsignedInteger('cliente_id')->index('cupom_ecommerce_utilizados_cliente_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cupom_ecommerce_utilizados');
    }
};
