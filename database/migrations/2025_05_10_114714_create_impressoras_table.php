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
        Schema::create('impressoras', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('impressoras_empresa_id_foreign');
            $table->string('descricao', 50);
            $table->string('porta', 20);
            $table->boolean('padrao')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impressoras');
    }
};
