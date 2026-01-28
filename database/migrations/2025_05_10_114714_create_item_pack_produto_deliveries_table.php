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
        Schema::create('item_pack_produto_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produto_delivery_id')->nullable()->index('item_pack_produto_deliveries_produto_delivery_id_foreign');
            $table->unsignedInteger('pack_id')->nullable()->index('item_pack_produto_deliveries_pack_id_foreign');
            $table->decimal('quantidade', 5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_pack_produto_deliveries');
    }
};
