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
        Schema::create('transferencias', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('transferencias_empresa_id_foreign');
            $table->unsignedInteger('filial_saida_id')->nullable()->index('transferencias_filial_saida_id_foreign');
            $table->unsignedInteger('filial_entrada_id')->nullable()->index('transferencias_filial_entrada_id_foreign');
            $table->unsignedInteger('usuario_id')->index('transferencias_usuario_id_foreign');
            $table->string('observacao')->nullable();
            $table->string('chave', 48)->nullable();
            $table->integer('numero_nfe')->nullable();
            $table->string('serie', 3)->nullable();
            $table->unsignedInteger('natureza_id')->nullable()->index('transferencias_natureza_id_foreign');
            $table->unsignedInteger('transportadora_id')->nullable()->index('transferencias_transportadora_id_foreign');
            $table->timestamp('data_emissao')->nullable();
            $table->integer('finNFe')->nullable();
            $table->integer('tpNF')->default(1);
            $table->integer('sequencia_cce')->default(0);
            $table->enum('estado', ['novo', 'rejeitado', 'cancelado', 'aprovado'])->default('novo');
            $table->string('motivo_cancelamento')->nullable();
            $table->timestamp('data_cancelamento')->nullable();
            $table->text('signed_xml')->nullable();
            $table->longText('cancelamento_xml')->nullable();
            $table->longText('carta_correcao_xml')->nullable();
            $table->text('carta_correcao_mensagem')->nullable();
            $table->timestamp('data_carta_correcao')->nullable();
            $table->integer('numero_carta_correcao')->default(0);
            $table->string('recibo', 30)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferencias');
    }
};
