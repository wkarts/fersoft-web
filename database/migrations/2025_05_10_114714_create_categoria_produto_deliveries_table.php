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
        Schema::create('categoria_produto_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('categoria_produto_deliveries_empresa_id_foreign');
            $table->string('nome', 30);
            $table->string('descricao', 120);
            $table->string('path', 80);
            $table->boolean('tipo_pizza')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categoria_produto_deliveries');
    }
};
