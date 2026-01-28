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
        Schema::create('carrossel_ecommerces', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('carrossel_ecommerces_empresa_id_foreign');
            $table->string('titulo', 30);
            $table->string('cor_titulo', 7)->default('#000');
            $table->string('descricao', 200);
            $table->string('cor_descricao', 7)->default('#000');
            $table->string('link_acao', 200);
            $table->string('nome_botao', 20);
            $table->string('img', 40);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carrossel_ecommerces');
    }
};
