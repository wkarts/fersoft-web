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
        Schema::create('divisao_grades', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 30);
            $table->unsignedInteger('empresa_id')->index('divisao_grades_empresa_id_foreign');
            $table->boolean('sub_divisao')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('divisao_grades');
    }
};
