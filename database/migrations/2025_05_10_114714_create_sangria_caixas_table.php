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
        Schema::create('sangria_caixas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('sangria_caixas_empresa_id_foreign');
            $table->unsignedInteger('usuario_id')->index('sangria_caixas_usuario_id_foreign');
            $table->timestamp('data_registro')->useCurrent();
            $table->decimal('valor', 10);
            $table->string('observacao', 50);
            $table->integer('conta_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sangria_caixas');
    }
};
