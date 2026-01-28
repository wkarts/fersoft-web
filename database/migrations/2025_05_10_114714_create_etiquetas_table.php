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
        Schema::create('etiquetas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 40);
            $table->string('observacao');
            $table->unsignedInteger('empresa_id')->nullable()->index('etiquetas_empresa_id_foreign');
            $table->string('altura', 10);
            $table->string('largura', 10);
            $table->integer('etiquestas_por_linha');
            $table->string('distancia_etiquetas_lateral', 10);
            $table->string('distancia_etiquetas_topo', 10);
            $table->integer('quantidade_etiquetas');
            $table->string('tamanho_fonte', 10);
            $table->string('tamanho_codigo_barras', 10);
            $table->boolean('nome_empresa');
            $table->boolean('nome_produto');
            $table->boolean('valor_produto');
            $table->boolean('codigo_produto');
            $table->boolean('codigo_barras_numerico');
            $table->enum('tipo', ['simples', 'gondola']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etiquetas');
    }
};
