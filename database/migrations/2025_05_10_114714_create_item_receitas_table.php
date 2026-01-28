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
        Schema::create('item_receitas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produto_id')->nullable()->index('item_receitas_produto_id_foreign');
            $table->unsignedInteger('receita_id')->nullable()->index('item_receitas_receita_id_foreign');
            $table->decimal('quantidade', 10, 3);
            $table->string('medida', 8);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_receitas');
    }
};
