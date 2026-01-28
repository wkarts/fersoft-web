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
        Schema::create('item_locacaos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('locacao_id')->index('item_locacaos_locacao_id_foreign');
            $table->unsignedInteger('produto_id')->index('item_locacaos_produto_id_foreign');
            $table->string('observacao', 50);
            $table->decimal('valor', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_locacaos');
    }
};
