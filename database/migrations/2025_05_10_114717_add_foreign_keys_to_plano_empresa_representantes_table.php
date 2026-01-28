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
        Schema::table('plano_empresa_representantes', function (Blueprint $table) {
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['plano_id'])->references(['id'])->on('planos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['representante_id'])->references(['id'])->on('representantes')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plano_empresa_representantes', function (Blueprint $table) {
            $table->dropForeign('plano_empresa_representantes_empresa_id_foreign');
            $table->dropForeign('plano_empresa_representantes_plano_id_foreign');
            $table->dropForeign('plano_empresa_representantes_representante_id_foreign');
        });
    }
};
