<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProdutoPrateleirasTable extends Migration
{
    public function up()
    {
        Schema::create('produto_prateleiras', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id')->nullable(false);
            $table->unsignedInteger('usuario_id')->nullable(false);
            $table->unsignedInteger('filial_id')->nullable();
            $table->string('identificacao', 50)->nullable(false);
            $table->string('descricao', 100)->nullable();
            $table->string('posicao', 10)->nullable();
            $table->string('localizacao', 100)->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();
            $table->softDeletes(); // <<-- adiciona campo deleted_at

            // Índices/foreign keys (ajuste conforme nomes das suas tabelas)
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->foreign('filial_id')->references('id')->on('filials')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('produto_prateleiras');
    }
}
