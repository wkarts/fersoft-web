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
        Schema::create('fornecedors', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('fornecedors_empresa_id_foreign');
            $table->string('razao_social', 100);
            $table->string('nome_fantasia', 80);
            $table->string('cpf_cnpj', 19);
            $table->string('ie_rg', 20);
            $table->string('rua', 80);
            $table->string('numero', 10);
            $table->string('bairro', 50);
            $table->string('telefone', 20);
            $table->string('complemento', 100)->default('');
            $table->string('celular', 20)->default('00 00000 0000');
            $table->string('email', 40);
            $table->string('cep', 10);
            $table->string('pix', 40)->nullable()->default('');
            $table->enum('tipo_pix', ['cpf', 'cnpj', 'email', 'telefone', 'chave aleatória'])->default('cpf');
            $table->unsignedInteger('cidade_id')->index('fornecedors_cidade_id_foreign');
            $table->integer('contribuinte');
            $table->integer('cod_pais')->default(1058);
            $table->string('id_estrangeiro', 30)->nullable()->default('');
            $table->string('imagem')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fornecedors');
    }
};
