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
        Schema::create('item_dves', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('item_dves_empresa_id_foreign');
            $table->integer('numero_nfe');
            $table->integer('produto_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_dves');
    }
};
