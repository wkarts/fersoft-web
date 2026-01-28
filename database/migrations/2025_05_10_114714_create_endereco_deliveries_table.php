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
        Schema::create('endereco_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cliente_id')->index('endereco_deliveries_cliente_id_foreign');
            $table->unsignedInteger('cidade_id')->index('endereco_deliveries_cidade_id_foreign');
            $table->string('rua', 50);
            $table->string('bairro', 30);
            $table->integer('bairro_id');
            $table->string('numero', 10);
            $table->string('referencia', 30);
            $table->string('latitude', 10);
            $table->string('longitude', 10);
            $table->string('cep', 10);
            $table->enum('tipo', ['casa', 'trabalho']);
            $table->boolean('principal');
            $table->boolean('padrao')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endereco_deliveries');
    }
};
