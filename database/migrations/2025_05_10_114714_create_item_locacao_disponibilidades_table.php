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
        Schema::create('item_locacao_disponibilidades', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produto_id')->index('item_locacao_disponibilidades_produto_id_foreign');
            $table->date('data');
            $table->integer('locacao_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_locacao_disponibilidades');
    }
};
