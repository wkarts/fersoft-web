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
        Schema::table('endereco_ecommerces', function (Blueprint $table) {
            $table->foreign(['cliente_id'])->references(['id'])->on('cliente_ecommerces')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('endereco_ecommerces', function (Blueprint $table) {
            $table->dropForeign('endereco_ecommerces_cliente_id_foreign');
        });
    }
};
