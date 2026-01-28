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
        Schema::create('erro_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('arquivo', 200);
            $table->string('linha', 10);
            $table->text('erro');
            $table->unsignedInteger('empresa_id')->index('erro_logs_empresa_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erro_logs');
    }
};
