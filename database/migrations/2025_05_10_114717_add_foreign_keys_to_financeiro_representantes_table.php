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
        Schema::table('financeiro_representantes', function (Blueprint $table) {
            $table->foreign(['representante_empresa_id'])->references(['id'])->on('representante_empresas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financeiro_representantes', function (Blueprint $table) {
            $table->dropForeign('financeiro_representantes_representante_empresa_id_foreign');
        });
    }
};
