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
        Schema::create('medida_ctes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cod_unidade', 2);
            $table->string('tipo_medida', 20);
            $table->decimal('quantidade_carga', 10, 4);
            $table->unsignedInteger('cte_id')->index('medida_ctes_cte_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medida_ctes');
    }
};
