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
        Schema::create('carrossel_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('carrossel_deliveries_empresa_id_foreign');
            $table->string('path', 30);
            $table->boolean('status')->default(true);
            $table->integer('valor_ordem')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carrossel_deliveries');
    }
};
