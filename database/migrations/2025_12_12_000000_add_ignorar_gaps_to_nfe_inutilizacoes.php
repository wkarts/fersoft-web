<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIgnorarGapsToNfeInutilizacoes extends Migration
{
    public function up()
    {
        Schema::table('nfe_inutilizacoes', function (Blueprint $table) {
            // Flag para dizer que esta inutilização deve "tirar" o gap da tela de gaps,
            // mesmo que tenha sido rejeitada pela SEFAZ
            $table->boolean('ignorar_gaps')
                ->default(false)
                ->after('status');

            // Motivo livre, para você saber por que está sendo ignorada
            $table->string('motivo_ignoracao', 255)
                ->nullable()
                ->after('ignorar_gaps');
        });
    }

    public function down()
    {
        Schema::table('nfe_inutilizacoes', function (Blueprint $table) {
            $table->dropColumn(['ignorar_gaps', 'motivo_ignoracao']);
        });
    }
}
