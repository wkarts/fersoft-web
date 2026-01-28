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
        Schema::create('funcionamento_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('funcionamento_deliveries_empresa_id_foreign');
            $table->boolean('ativo');
            $table->string('dia');
            $table->string('inicio_expediente', 5);
            $table->string('fim_expediente', 5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funcionamento_deliveries');
    }
};
