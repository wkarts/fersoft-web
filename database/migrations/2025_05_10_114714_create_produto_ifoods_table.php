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
        Schema::create('produto_ifoods', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('produto_ifoods_empresa_id_foreign');
            $table->unsignedInteger('categoria_id')->index('produto_ifoods_categoria_id_foreign');
            $table->string('id_ifood', 50);
            $table->string('id_ifood_aux', 50);
            $table->string('nome', 150);
            $table->text('descricao');
            $table->string('imagem', 200);
            $table->string('serving', 20)->nullable();
            $table->string('ean', 20)->nullable();
            $table->decimal('valor', 10)->nullable();
            $table->string('status', 20)->nullable();
            $table->decimal('estoque', 10)->nullable();
            $table->integer('sellingOption_minimum')->nullable();
            $table->integer('sellingOption_incremental')->nullable();
            $table->integer('sellingOption_averageUnit')->nullable();
            $table->string('sellingOption_availableUnits', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produto_ifoods');
    }
};
