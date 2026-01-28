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
        Schema::create('config_systems', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cor', 10);
            $table->string('mensagem_plano_indeterminado', 250)->nullable();
            $table->integer('inicio_mensagem_plano')->nullable();
            $table->integer('fim_mensagem_plano')->nullable();
            $table->decimal('valor_base_contrato', 10)->nullable();
            $table->string('usuario_correios', 30)->nullable();
            $table->string('codigo_acesso_correios', 100)->nullable();
            $table->string('cartao_postagem_correios', 100)->nullable();
            $table->text('token_correios');
            $table->string('token_expira_correios', 30);
            $table->string('dr_correios', 30);
            $table->string('contrato_correios', 30);
            $table->string('token_integra_notas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_systems');
    }
};
