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
        Schema::create('natureza_operacaos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('natureza_operacaos_empresa_id_foreign');
            $table->string('natureza', 80);
            $table->string('CFOP_entrada_estadual', 5)->default('');
            $table->string('CFOP_entrada_inter_estadual', 5)->default('');
            $table->string('CFOP_saida_estadual', 5)->default('');
            $table->string('CFOP_saida_inter_estadual', 5)->default('');
            $table->boolean('sobrescreve_cfop')->default(false);
            $table->integer('finNFe')->default(1);
            $table->boolean('nao_movimenta_estoque')->default(false);
            $table->string('CST_CSOSN', 3)->nullable();
            $table->string('categoria_conta_id', 3)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('natureza_operacaos');
    }
};
