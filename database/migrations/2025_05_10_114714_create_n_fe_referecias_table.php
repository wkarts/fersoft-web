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
        Schema::create('n_fe_referecias', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venda_id')->index('n_fe_referecias_venda_id_foreign');
            $table->string('chave', 44);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('n_fe_referecias');
    }
};
