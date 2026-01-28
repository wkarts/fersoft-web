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
        Schema::table('fatura_doc_ctes', function (Blueprint $table) {
            $table->foreign(['cte_id'])->references(['id'])->on('ctes')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['fatura_id'])->references(['id'])->on('fatura_ctes')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fatura_doc_ctes', function (Blueprint $table) {
            $table->dropForeign('fatura_doc_ctes_cte_id_foreign');
            $table->dropForeign('fatura_doc_ctes_fatura_id_foreign');
        });
    }
};
