<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNfeInutilizacoesTable extends Migration
{
    public function up()
    {
        Schema::create('nfe_inutilizacoes', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('filial_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();

            $table->string('modelo', 2)->default('55');
            $table->integer('serie');
            $table->integer('numero_inicial');
            $table->integer('numero_final');
            $table->string('ano', 2);
            $table->string('justificativa', 255);
            $table->string('ambiente', 20)->default('producao');
            $table->string('origem', 50)->nullable(); // ex: salto_numeracao

            $table->string('status', 20)->nullable();      // AUTORIZADA, REJEITADA, etc
            $table->string('protocolo', 50)->nullable();
            $table->string('mensagem', 255)->nullable();
            $table->string('nfserver_id', 100)->nullable();

            $table->longText('xml_solicitacao')->nullable();
            $table->longText('xml_retorno')->nullable();

            $table->timestamps();

            $table->index(['empresa_id', 'filial_id', 'modelo', 'serie']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('nfe_inutilizacoes');
    }
}
