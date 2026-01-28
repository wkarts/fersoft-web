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
        Schema::create('dres', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('dres_empresa_id_foreign');
            $table->date('inicio');
            $table->date('fim');
            $table->string('observacao', 250);
            $table->decimal('percentual_imposto', 5)->default(0);
            $table->decimal('lucro_prejuizo', 12)->default(0);
            $table->unsignedInteger('filial_id')->nullable()->index('dres_filial_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dres');
    }
};
