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
        Schema::create('fretes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('placa', 9);
            $table->string('uf', 2);
            $table->decimal('valor', 10);
            $table->integer('tipo');
            $table->integer('qtdVolumes');
            $table->string('numeracaoVolumes', 20);
            $table->string('especie', 20);
            $table->decimal('peso_liquido', 20, 3);
            $table->decimal('peso_bruto', 20, 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fretes');
    }
};
