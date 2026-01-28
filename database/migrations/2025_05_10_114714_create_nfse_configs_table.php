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
        Schema::create('nfse_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 100);
            $table->string('razao_social', 100);
            $table->string('documento', 18);
            $table->enum('regime', ['simples', 'normal']);
            $table->string('ie', 20)->nullable();
            $table->string('im', 20)->nullable();
            $table->string('cnae', 20)->nullable();
            $table->string('login_prefeitura', 50)->nullable();
            $table->string('senha_prefeitura', 50)->nullable();
            $table->string('telefone', 20);
            $table->string('email', 80);
            $table->string('rua', 80);
            $table->string('numero', 10);
            $table->string('bairro', 30);
            $table->string('complemento', 50)->nullable();
            $table->string('cep', 9);
            $table->string('token')->nullable();
            $table->string('logo', 30)->nullable();
            $table->unsignedInteger('cidade_id')->nullable()->index('nfse_configs_cidade_id_foreign');
            $table->unsignedInteger('empresa_id')->index('nfse_configs_empresa_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfse_configs');
    }
};
