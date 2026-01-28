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
        Schema::table('fatura_venda_balcaos', function (Blueprint $table) {
            $table->foreign(['venda_balcao_id'])->references(['id'])->on('venda_balcaos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fatura_venda_balcaos', function (Blueprint $table) {
            $table->dropForeign('fatura_venda_balcaos_venda_balcao_id_foreign');
        });
    }
};
