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
        Schema::table('aviso_acessos', function (Blueprint $table) {
            $table->foreign(['aviso_id'])->references(['id'])->on('avisos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aviso_acessos', function (Blueprint $table) {
            $table->dropForeign('aviso_acessos_aviso_id_foreign');
            $table->dropForeign('aviso_acessos_empresa_id_foreign');
        });
    }
};
