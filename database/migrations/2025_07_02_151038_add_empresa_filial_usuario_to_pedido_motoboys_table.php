<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmpresaFilialUsuarioToPedidoMotoboysTable extends Migration
{
    public function up()
    {
        Schema::table('pedido_motoboys', function (Blueprint $table) {
            // empresa_id (qual empresa registra o frete)
            $table->unsignedInteger('empresa_id')->nullable(false)->after('pedido_id');
            // filial_id (de onde saiu o pedido)
            $table->unsignedInteger('filial_id')->nullable()->after('empresa_id');
            // usuario_id (quem cadastrou/atualizou)
            $table->unsignedInteger('usuario_id')->nullable(false)->after('filial_id');

            // FKs
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('filial_id')->references('id')->on('filials')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('pedido_motoboys', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->dropForeign(['filial_id']);
            $table->dropForeign(['empresa_id']);
            $table->dropColumn(['usuario_id', 'filial_id', 'empresa_id']);
        });
    }
}
