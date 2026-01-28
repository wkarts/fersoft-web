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
        Schema::create('endereco_ecommerces', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cliente_id')->index('endereco_ecommerces_cliente_id_foreign');
            $table->string('rua', 60);
            $table->string('numero', 10);
            $table->string('bairro', 30);
            $table->string('cidade', 30);
            $table->string('uf', 2);
            $table->string('cep', 9);
            $table->string('complemento', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endereco_ecommerces');
    }
};
