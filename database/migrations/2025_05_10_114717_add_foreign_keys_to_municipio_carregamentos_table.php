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
        Schema::table('municipio_carregamentos', function (Blueprint $table) {
            $table->foreign(['cidade_id'])->references(['id'])->on('cidades')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['mdfe_id'])->references(['id'])->on('mdves')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('municipio_carregamentos', function (Blueprint $table) {
            $table->dropForeign('municipio_carregamentos_cidade_id_foreign');
            $table->dropForeign('municipio_carregamentos_mdfe_id_foreign');
        });
    }
};
