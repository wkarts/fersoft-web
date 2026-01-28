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
        Schema::table('item_transferencias', function (Blueprint $table) {
            $table->foreign(['produto_id'])->references(['id'])->on('produtos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['transferencia_id'])->references(['id'])->on('transferencias')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_transferencias', function (Blueprint $table) {
            $table->dropForeign('item_transferencias_produto_id_foreign');
            $table->dropForeign('item_transferencias_transferencia_id_foreign');
        });
    }
};
