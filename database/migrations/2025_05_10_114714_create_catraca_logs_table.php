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
        Schema::create('catraca_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('catraca_logs_empresa_id_foreign');
            $table->string('comanda', 10);
            $table->enum('tipo', ['L', 'C']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catraca_logs');
    }
};
