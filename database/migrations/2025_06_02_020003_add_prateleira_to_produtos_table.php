<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrateleiraToProdutosTable extends Migration
{
    public function up()
    {
        Schema::table('produtos', function (Blueprint $table) {
            // 1) adiciona a coluna produto_prateleira_id (nullable para não quebrar existentes)
            $table->unsignedBigInteger('produto_prateleira_id')->nullable()->after('produto_referenciado_id');

            // 2) cria a FK apontando para produtos_prateleiras(id)
            $table->foreign('produto_prateleira_id')
                ->references('id')
                ->on('produto_prateleiras')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('produtos', function (Blueprint $table) {
            // desfaz FK e coluna
            $table->dropForeign(['produto_prateleira_id']);
            $table->dropColumn('produto_prateleira_id');
        });
    }
}
