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
        Schema::create('representantes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 80);
            $table->string('rua', 50);
            $table->string('telefone', 15);
            $table->string('email', 50);
            $table->string('numero', 10);
            $table->string('bairro', 30);
            $table->string('cidade', 30);
            $table->string('cpf_cnpj', 18);
            $table->boolean('status')->default(true);
            $table->decimal('comissao', 5)->default(0);
            $table->unsignedInteger('usuario_id')->index('representantes_usuario_id_foreign');
            $table->boolean('acesso_xml')->default(false);
            $table->boolean('mensagem_cobranca_login')->default(false);
            $table->boolean('bloquear_empresa')->default(false);
            $table->integer('limite_cadastros')->default(1);
            $table->string('senha_master', 80)->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('representantes');
    }
};
