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
        Schema::create('pushes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('pushes_empresa_id_foreign');
            $table->unsignedInteger('cliente_id')->nullable()->index('pushes_cliente_id_foreign');
            $table->string('titulo', 50);
            $table->string('texto', 100);
            $table->string('path_img', 200);
            $table->integer('referencia_produto');
            $table->timestamp('data_registro')->useCurrent();
            $table->boolean('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pushes');
    }
};
