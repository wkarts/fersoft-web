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
        Schema::create('clientes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->nullable()->index('clientes_empresa_id_foreign');
            $table->string('razao_social', 100);
            $table->string('nome_fantasia', 80);
            $table->string('cpf_cnpj', 19)->default('000.000.000-00');
            $table->string('rua', 80);
            $table->string('ie_rg', 20);
            $table->string('numero', 10);
            $table->string('bairro', 50);
            $table->string('telefone', 20);
            $table->string('complemento', 100)->default('');
            $table->string('celular', 20)->default('00 00000 0000');
            $table->string('email', 60)->nullable();
            $table->string('cep', 10)->default('');
            $table->integer('consumidor_final');
            $table->integer('contribuinte');
            $table->boolean('inativo')->default(false);
            $table->unsignedInteger('cidade_id')->nullable()->index('clientes_cidade_id_foreign');
            $table->decimal('limite_venda', 10)->default(0);
            $table->decimal('valor_cashback', 10)->default(0);
            $table->string('rua_cobranca', 100);
            $table->string('numero_cobranca', 10);
            $table->string('bairro_cobranca', 30);
            $table->string('cep_cobranca', 9);
            $table->unsignedInteger('cidade_cobranca_id')->nullable()->index('clientes_cidade_cobranca_id_foreign');
            $table->integer('cod_pais')->default(1058);
            $table->string('id_estrangeiro', 30)->default('');
            $table->integer('grupo_id')->default(0);
            $table->integer('acessor_id')->default(0);
            $table->string('contador_nome', 30)->default('');
            $table->string('contador_telefone', 15)->default('');
            $table->string('contador_email', 60)->default('');
            $table->integer('funcionario_id')->default(0);
            $table->string('observacao')->default('');
            $table->string('data_aniversario', 5)->default('');
            $table->string('data_nascimento', 10)->default('');
            $table->string('nuvemshop_id', 20)->default('');
            $table->string('imagem', 30)->default('');
            $table->string('instagram')->default('');
            $table->string('facebook')->default('');
            $table->string('linkedin')->default('');
            $table->string('tiktok')->default('');
            $table->string('whatsapp')->default('');
            $table->string('rua_entrega', 100);
            $table->string('nome_entrega', 80);
            $table->string('cpf_cnpj_entrega', 20);
            $table->string('numero_entrega', 10);
            $table->string('bairro_entrega', 30);
            $table->string('cep_entrega', 9);
            $table->integer('cidade_entrega_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
