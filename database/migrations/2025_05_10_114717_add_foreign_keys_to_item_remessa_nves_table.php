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
        Schema::table('item_remessa_nves', function (Blueprint $table) {
            $table->foreign(['produto_id'])->references(['id'])->on('produtos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['remessa_id'])->references(['id'])->on('remessa_nves')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_remessa_nves', function (Blueprint $table) {
            $table->dropForeign('item_remessa_nves_produto_id_foreign');
            $table->dropForeign('item_remessa_nves_remessa_id_foreign');
        });
    }
};
