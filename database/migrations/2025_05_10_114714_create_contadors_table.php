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
        Schema::create('contadors', function (Blueprint $table) {
            $table->increments('id');
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
            $table->decimal('percentual_comissao', 5);
            $table->integer('cidade_id');
            $table->boolean('cadastrado_por_cliente')->default(false);
            $table->boolean('contador_parceiro')->default(false);
            $table->string('dados_bancarios')->default('');
            $table->string('agencia', 15)->default('');
            $table->string('conta', 15)->default('');
            $table->string('banco', 30)->default('');
            $table->string('chave_pix', 50)->default('');
            $table->integer('empresa_id')->nullable();
            $table->integer('representante_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contadors');
    }
};
