<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFilialIdUsuarioIdToPedidosTable extends Migration
{
    public function up()
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // filial_id opcional (quando for pedido “Matriz” ou sem filiais)
            $table->unsignedInteger('filial_id')->nullable()->after('empresa_id');
            // usuario_id obrigatório (quem criou/atualizou o pedido)
            $table->unsignedInteger('usuario_id')->nullable(false)->after('filial_id');

            // chaves estrangeiras
            $table->foreign('filial_id')->references('id')->on('filials')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->dropForeign(['filial_id']);
            $table->dropColumn(['usuario_id', 'filial_id']);
        });
    }
}
