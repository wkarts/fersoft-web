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
        Schema::table('apuracao_salario_eventos', function (Blueprint $table) {
            $table->foreign(['apuracao_id'])->references(['id'])->on('apuracao_salarios')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['evento_id'])->references(['id'])->on('evento_salarios')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apuracao_salario_eventos', function (Blueprint $table) {
            $table->dropForeign('apuracao_salario_eventos_apuracao_id_foreign');
            $table->dropForeign('apuracao_salario_eventos_evento_id_foreign');
        });
    }
};
