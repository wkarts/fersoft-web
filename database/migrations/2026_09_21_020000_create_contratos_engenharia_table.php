<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('contratos_engenharia')) {
            return;
        }

        Schema::create('contratos_engenharia', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('filial_id')->nullable();
            $table->unsignedInteger('usuario_id')->nullable();
            $table->unsignedInteger('cliente_id');
            $table->unsignedInteger('vendedor_id')->nullable();
            $table->unsignedInteger('centro_custo_id')->nullable();
            $table->string('numero_contrato', 100)->nullable();
            $table->string('contato_nome', 191)->nullable();
            $table->string('contato_telefone', 40)->nullable();
            $table->string('cep_obra', 20)->nullable();
            $table->string('endereco_obra', 255)->nullable();
            $table->string('numero_obra', 30)->nullable();
            $table->string('bairro_obra', 191)->nullable();
            $table->unsignedInteger('cidade_obra_id')->nullable();
            $table->decimal('valor_contrato', 15, 2)->default(0);
            $table->decimal('valor_faturado', 15, 2)->default(0);
            $table->decimal('percentual_retencao', 7, 2)->default(0);
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->string('arquivo_contrato', 255)->nullable();
            $table->string('status', 30)->default('Ativo');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status'], 'contratos_eng_empresa_status_idx');
            $table->index(['empresa_id', 'cliente_id'], 'contratos_eng_empresa_cliente_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contratos_engenharia');
    }
};
