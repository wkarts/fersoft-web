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
        Schema::create('transportadoras', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('transportadoras_empresa_id_foreign');
            $table->string('razao_social', 100);
            $table->string('cnpj_cpf', 19)->default('000.000.000-00');
            $table->string('logradouro', 80);
            $table->string('numero', 20);
            $table->unsignedInteger('cidade_id')->index('transportadoras_cidade_id_foreign');
            $table->string('email', 40)->default('');
            $table->string('telefone', 20)->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transportadoras');
    }
};
