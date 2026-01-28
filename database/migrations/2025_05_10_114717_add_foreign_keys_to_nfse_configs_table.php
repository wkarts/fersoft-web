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
        Schema::table('nfse_configs', function (Blueprint $table) {
            $table->foreign(['cidade_id'])->references(['id'])->on('cidades')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nfse_configs', function (Blueprint $table) {
            $table->dropForeign('nfse_configs_cidade_id_foreign');
            $table->dropForeign('nfse_configs_empresa_id_foreign');
        });
    }
};
