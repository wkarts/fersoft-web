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
        Schema::table('cotacaos', function (Blueprint $table) {
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['fornecedor_id'])->references(['id'])->on('fornecedors')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotacaos', function (Blueprint $table) {
            $table->dropForeign('cotacaos_empresa_id_foreign');
            $table->dropForeign('cotacaos_fornecedor_id_foreign');
        });
    }
};
