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
        Schema::create('motoboys', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('motoboys_empresa_id_foreign');
            $table->string('nome', 60);
            $table->string('celular', 15);
            $table->string('rua', 60);
            $table->string('numero', 10);
            $table->string('bairro', 30);
            $table->decimal('valor_entrega_padrao', 7);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motoboys');
    }
};
