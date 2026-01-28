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
        Schema::create('suprimento_caixas', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->unsignedInteger('empresa_id')->index('suprimento_caixas_empresa_id_foreign');
            $table->unsignedInteger('usuario_id')->index('suprimento_caixas_usuario_id_foreign');
            $table->string('observacao', 50);
            $table->decimal('valor', 10);
            $table->string('tipo', 2)->nullable();
            $table->integer('conta_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suprimento_caixas');
    }
};
