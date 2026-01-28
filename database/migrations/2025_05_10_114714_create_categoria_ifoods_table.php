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
        Schema::create('categoria_ifoods', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('categoria_ifoods_empresa_id_foreign');
            $table->string('id_ifood', 50);
            $table->string('nome', 100);
            $table->string('status', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categoria_ifoods');
    }
};
