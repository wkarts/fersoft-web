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
        Schema::create('manifesta_ctes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('manifesta_ctes_empresa_id_foreign');
            $table->string('chave', 44);
            $table->string('nome', 100);
            $table->string('documento', 20);
            $table->decimal('valor', 10);
            $table->string('data_emissao', 25);
            $table->integer('sequencia_evento');
            $table->integer('tipo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manifesta_ctes');
    }
};
