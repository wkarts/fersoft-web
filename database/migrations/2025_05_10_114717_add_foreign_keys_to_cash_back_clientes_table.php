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
        Schema::table('cash_back_clientes', function (Blueprint $table) {
            $table->foreign(['cliente_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_back_clientes', function (Blueprint $table) {
            $table->dropForeign('cash_back_clientes_cliente_id_foreign');
            $table->dropForeign('cash_back_clientes_empresa_id_foreign');
        });
    }
};
