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
        Schema::create('manifesta_dves', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('manifesta_dves_empresa_id_foreign');
            $table->string('chave', 44);
            $table->string('nome', 100);
            $table->string('documento', 20);
            $table->decimal('valor', 10);
            $table->string('num_prot', 20);
            $table->string('data_emissao', 25);
            $table->integer('sequencia_evento');
            $table->boolean('fatura_salva');
            $table->integer('tipo');
            $table->integer('nsu');
            $table->integer('nNf')->default(0);
            $table->integer('compra_id');
            $table->integer('venda_id');
            $table->unsignedInteger('filial_id')->nullable()->index('manifesta_dves_filial_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manifesta_dves');
    }
};
