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
        Schema::create('produto_destaque_master_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produto_id')->index('produto_destaque_master_deliveries_produto_id_foreign');
            $table->unsignedInteger('categoria_id')->index('produto_destaque_master_deliveries_categoria_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produto_destaque_master_deliveries');
    }
};
