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
        Schema::table('n_fe_referecias', function (Blueprint $table) {
            $table->foreign(['venda_id'])->references(['id'])->on('vendas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('n_fe_referecias', function (Blueprint $table) {
            $table->dropForeign('n_fe_referecias_venda_id_foreign');
        });
    }
};
