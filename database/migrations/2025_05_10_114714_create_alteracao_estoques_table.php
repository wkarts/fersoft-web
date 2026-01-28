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
        Schema::create('alteracao_estoques', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('alteracao_estoques_empresa_id_foreign');
            $table->unsignedInteger('produto_id')->nullable()->index('alteracao_estoques_produto_id_foreign');
            $table->unsignedInteger('usuario_id')->nullable()->index('alteracao_estoques_usuario_id_foreign');
            $table->decimal('quantidade', 15, 3);
            $table->string('observacao', 200);
            $table->string('tipo', 15);
            $table->string('motivo', 25)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alteracao_estoques');
    }
};
