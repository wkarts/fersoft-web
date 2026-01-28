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
        Schema::table('compra_referencias', function (Blueprint $table) {
            $table->foreign(['compra_id'])->references(['id'])->on('compras')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compra_referencias', function (Blueprint $table) {
            $table->dropForeign('compra_referencias_compra_id_foreign');
        });
    }
};
