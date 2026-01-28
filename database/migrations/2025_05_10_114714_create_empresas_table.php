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
        Schema::create('empresas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 80);
            $table->string('nome_fantasia', 80)->nullable();
            $table->string('rua', 50);
            $table->string('telefone', 15);
            $table->string('email', 50);
            $table->string('numero', 10);
            $table->string('bairro', 30);
            $table->string('cidade', 30);
            $table->string('uf', 2)->nullable();
            $table->string('cep', 9)->nullable();
            $table->string('cnpj', 18);
            $table->text('permissao');
            $table->boolean('status')->default(true);
            $table->boolean('tipo_representante')->default(false);
            $table->boolean('tipo_contador')->default(false);
            $table->integer('perfil_id')->default(0);
            $table->string('mensagem_bloqueio')->default('');
            $table->string('info_contador')->default('');
            $table->integer('contador_id')->default(0);
            $table->string('representante_legal', 100)->nullable();
            $table->string('cpf_representante_legal', 15)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
