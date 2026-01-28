<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFilialIdUsuarioIdToPedidoDeliveriesTable extends Migration
{
    public function up()
    {
        Schema::table('pedido_deliveries', function (Blueprint $table) {
            $table->unsignedInteger('filial_id')->nullable()->after('empresa_id');
            $table->unsignedInteger('usuario_id')->nullable(false)->after('filial_id');

            $table->foreign('filial_id')->references('id')->on('filials')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('pedido_deliveries', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->dropForeign(['filial_id']);
            $table->dropColumn(['usuario_id', 'filial_id']);
        });
    }
}
