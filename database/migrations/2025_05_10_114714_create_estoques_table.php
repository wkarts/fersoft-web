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
        Schema::create('estoques', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('estoques_empresa_id_foreign');
            $table->unsignedInteger('produto_id')->index('estoques_produto_id_foreign');
            $table->decimal('quantidade', 10, 3);
            $table->decimal('valor_compra', 10);
            $table->unsignedInteger('filial_id')->nullable()->index('estoques_filial_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estoques');
    }
};
