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
        Schema::create('inventarios', function (Blueprint $table) {
            $table->increments('id');
            $table->date('inicio');
            $table->date('fim');
            $table->boolean('status')->default(true);
            $table->string('referencia', 30);
            $table->string('observacao');
            $table->string('tipo', 15);
            $table->unsignedInteger('empresa_id')->index('inventarios_empresa_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};
