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
        Schema::table('banner_topos', function (Blueprint $table) {
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['pack_id'])->references(['id'])->on('pack_produto_deliveries')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['produto_delivery_id'])->references(['id'])->on('produto_deliveries')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banner_topos', function (Blueprint $table) {
            $table->dropForeign('banner_topos_empresa_id_foreign');
            $table->dropForeign('banner_topos_pack_id_foreign');
            $table->dropForeign('banner_topos_produto_delivery_id_foreign');
        });
    }
};
