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
        Schema::create('servicos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('servicos_empresa_id_foreign');
            $table->string('nome', 60);
            $table->decimal('valor', 10);
            $table->string('unidade_cobranca', 5);
            $table->integer('tempo_servico');
            $table->integer('tempo_adicional')->default(0);
            $table->integer('tempo_tolerancia')->default(0);
            $table->decimal('valor_adicional', 10)->default(0);
            $table->decimal('comissao', 6)->default(0);
            $table->unsignedInteger('categoria_id')->index('servicos_categoria_id_foreign');
            $table->string('codigo_servico', 10)->nullable();
            $table->decimal('aliquota_iss', 6)->default(0);
            $table->decimal('aliquota_pis', 6)->default(0);
            $table->decimal('aliquota_cofins', 6)->default(0);
            $table->decimal('aliquota_inss', 6)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicos');
    }
};
