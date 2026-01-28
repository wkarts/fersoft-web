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
        Schema::table('token_cliente_deliveries', function (Blueprint $table) {
            $table->foreign(['cliente_id'])->references(['id'])->on('cliente_deliveries')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('token_cliente_deliveries', function (Blueprint $table) {
            $table->dropForeign('token_cliente_deliveries_cliente_id_foreign');
        });
    }
};
