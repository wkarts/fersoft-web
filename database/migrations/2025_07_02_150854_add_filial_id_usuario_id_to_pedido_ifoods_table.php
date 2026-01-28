<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFilialIdUsuarioIdToPedidoIfoodsTable extends Migration
{
    public function up()
    {
        Schema::table('pedido_ifoods', function (Blueprint $table) {
            // filial_id pode ser nulo (quando não houver filial)
            $table->unsignedInteger('filial_id')->nullable()->after('empresa_id');
            // usuario_id obrigatório (quem importou/processou)
            $table->unsignedInteger('usuario_id')->nullable(false)->after('filial_id');

            // FKs
            $table->foreign('filial_id')->references('id')->on('filials')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('pedido_ifoods', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->dropForeign(['filial_id']);
            $table->dropColumn(['usuario_id', 'filial_id']);
        });
    }
}
