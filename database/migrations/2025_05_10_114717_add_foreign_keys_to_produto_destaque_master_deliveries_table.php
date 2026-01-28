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
        Schema::table('produto_destaque_master_deliveries', function (Blueprint $table) {
            $table->foreign(['categoria_id'])->references(['id'])->on('categoria_destaque_master_deliveries')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['produto_id'])->references(['id'])->on('produto_deliveries')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produto_destaque_master_deliveries', function (Blueprint $table) {
            $table->dropForeign('produto_destaque_master_deliveries_categoria_id_foreign');
            $table->dropForeign('produto_destaque_master_deliveries_produto_id_foreign');
        });
    }
};
