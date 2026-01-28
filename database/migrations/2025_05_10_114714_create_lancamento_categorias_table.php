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
        Schema::create('lancamento_categorias', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('categoria_id')->index('lancamento_categorias_categoria_id_foreign');
            $table->string('nome', 100);
            $table->decimal('valor', 10);
            $table->decimal('percentual', 5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lancamento_categorias');
    }
};
