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
        Schema::create('mdves', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('mdves_empresa_id_foreign');
            $table->string('uf_inicio', 2);
            $table->string('uf_fim', 2);
            $table->boolean('encerrado');
            $table->date('data_inicio_viagem');
            $table->boolean('carga_posterior');
            $table->string('cnpj_contratante', 18);
            $table->unsignedInteger('veiculo_tracao_id')->index('mdves_veiculo_tracao_id_foreign');
            $table->unsignedInteger('veiculo_reboque_id')->nullable()->index('mdves_veiculo_reboque_id_foreign');
            $table->unsignedInteger('veiculo_reboque2_id')->nullable()->index('mdves_veiculo_reboque2_id_foreign');
            $table->unsignedInteger('veiculo_reboque3_id')->nullable()->index('mdves_veiculo_reboque3_id_foreign');
            $table->string('estado', 20);
            $table->integer('mdfe_numero');
            $table->string('chave', 44);
            $table->string('protocolo', 16);
            $table->string('seguradora_nome', 30);
            $table->string('seguradora_cnpj', 18);
            $table->string('numero_apolice', 15);
            $table->string('numero_averbacao', 40);
            $table->decimal('valor_carga', 10);
            $table->decimal('quantidade_carga', 10, 4);
            $table->string('info_complementar', 60);
            $table->string('info_adicional_fisco', 60);
            $table->string('condutor_nome', 60);
            $table->string('condutor_cpf', 15);
            $table->string('lac_rodo', 8);
            $table->integer('tp_emit');
            $table->integer('tp_transp')->nullable();
            $table->string('produto_pred_nome', 50);
            $table->string('produto_pred_ncm', 8);
            $table->string('produto_pred_cod_barras', 13);
            $table->string('cep_carrega', 8);
            $table->string('cep_descarrega', 8);
            $table->string('tp_carga', 2);
            $table->string('latitude_carregamento', 15);
            $table->string('longitude_carregamento', 15);
            $table->string('latitude_descarregamento', 15);
            $table->string('longitude_descarregamento', 15);
            $table->unsignedInteger('filial_id')->nullable()->index('mdves_filial_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mdves');
    }
};
