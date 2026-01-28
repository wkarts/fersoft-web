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
        Schema::create('item_transferencias', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('transferencia_id')->index('item_transferencias_transferencia_id_foreign');
            $table->unsignedInteger('produto_id')->index('item_transferencias_produto_id_foreign');
            $table->decimal('quantidade', 10, 3);
            $table->decimal('valor_unitario', 10, 4)->default(0);
            $table->timestamps();
            $table->decimal('sub_total', 15, 4)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_transferencias');
    }
};
