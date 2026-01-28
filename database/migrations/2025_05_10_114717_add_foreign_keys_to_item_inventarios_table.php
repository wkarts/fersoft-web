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
        Schema::table('item_inventarios', function (Blueprint $table) {
            $table->foreign(['inventario_id'])->references(['id'])->on('inventarios')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['produto_id'])->references(['id'])->on('produtos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['usuario_id'])->references(['id'])->on('usuarios')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_inventarios', function (Blueprint $table) {
            $table->dropForeign('item_inventarios_inventario_id_foreign');
            $table->dropForeign('item_inventarios_produto_id_foreign');
            $table->dropForeign('item_inventarios_usuario_id_foreign');
        });
    }
};
