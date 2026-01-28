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
        Schema::create('acessors', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('acessors_empresa_id_foreign');
            $table->string('razao_social', 100);
            $table->string('cpf_cnpj', 19)->default('000.000.000-00');
            $table->string('rua', 80);
            $table->string('ie_rg', 20);
            $table->string('numero', 10);
            $table->string('bairro', 50);
            $table->string('telefone', 20);
            $table->string('celular', 20)->default('00 00000 0000');
            $table->string('email', 40)->default('');
            $table->string('cep', 10)->default('');
            $table->enum('tipo_comissao', ['percentual', 'custo'])->default('percentual');
            $table->decimal('percentual_comissao', 6)->default(0);
            $table->date('data_registro');
            $table->integer('funcionario_id');
            $table->boolean('ativo')->default(true);
            $table->unsignedInteger('cidade_id')->index('acessors_cidade_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acessors');
    }
};
