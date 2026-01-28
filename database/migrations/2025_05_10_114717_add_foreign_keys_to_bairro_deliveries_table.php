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
        Schema::table('bairro_deliveries', function (Blueprint $table) {
            $table->foreign(['cidade_id'])->references(['id'])->on('cidade_deliveries')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bairro_deliveries', function (Blueprint $table) {
            $table->dropForeign('bairro_deliveries_cidade_id_foreign');
        });
    }
};
