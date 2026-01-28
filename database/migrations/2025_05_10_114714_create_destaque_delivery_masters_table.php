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
        Schema::create('destaque_delivery_masters', function (Blueprint $table) {
            $table->increments('id');
            $table->string('img', 80);
            $table->string('titulo', 50);
            $table->string('descricao', 250);
            $table->string('acao', 250)->default('');
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destaque_delivery_masters');
    }
};
