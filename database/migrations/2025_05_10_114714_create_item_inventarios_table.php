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
        Schema::create('item_inventarios', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('inventario_id')->index('item_inventarios_inventario_id_foreign');
            $table->unsignedInteger('produto_id')->index('item_inventarios_produto_id_foreign');
            $table->unsignedInteger('usuario_id')->index('item_inventarios_usuario_id_foreign');
            $table->decimal('quantidade', 10);
            $table->string('observacao', 100);
            $table->string('estado', 15);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_inventarios');
    }
};
