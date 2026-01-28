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
        Schema::create('item_orcamentos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('orcamento_id')->index('item_orcamentos_orcamento_id_foreign');
            $table->unsignedInteger('produto_id')->index('item_orcamentos_produto_id_foreign');
            $table->decimal('quantidade', 16, 7);
            $table->decimal('valor', 16, 7);
            $table->decimal('altura', 10)->nullable();
            $table->decimal('largura', 10)->nullable();
            $table->decimal('profundidade', 10)->nullable();
            $table->decimal('acrescimo_perca', 10)->nullable();
            $table->decimal('esquerda', 10)->nullable();
            $table->decimal('direita', 10)->nullable();
            $table->decimal('inferior', 10)->nullable();
            $table->decimal('superior', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_orcamentos');
    }
};
