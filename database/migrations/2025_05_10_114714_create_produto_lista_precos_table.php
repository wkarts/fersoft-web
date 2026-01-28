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
        Schema::create('produto_lista_precos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('lista_id')->index('produto_lista_precos_lista_id_foreign');
            $table->unsignedInteger('produto_id')->index('produto_lista_precos_produto_id_foreign');
            $table->decimal('valor', 10);
            $table->decimal('percentual_lucro', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produto_lista_precos');
    }
};
