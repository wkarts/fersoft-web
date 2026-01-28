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
        Schema::create('escritorio_contabils', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('escritorio_contabils_empresa_id_foreign');
            $table->string('razao_social', 100);
            $table->string('nome_fantasia', 80);
            $table->string('cnpj', 19);
            $table->string('ie', 20);
            $table->string('logradouro', 80);
            $table->string('numero', 10);
            $table->string('bairro', 50);
            $table->string('fone', 20);
            $table->string('cep', 10);
            $table->string('email', 80);
            $table->string('crc', 20)->nullable();
            $table->string('cpf', 15)->nullable();
            $table->string('token_sieg');
            $table->boolean('envio_automatico_xml_contador')->default(false);
            $table->integer('cidade_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escritorio_contabils');
    }
};
