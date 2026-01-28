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
        Schema::create('imagens_produto_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produto_id')->index('imagens_produto_deliveries_produto_id_foreign');
            $table->string('path', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imagens_produto_deliveries');
    }
};
