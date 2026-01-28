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
        Schema::create('compra_referencias', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('compra_id')->index('compra_referencias_compra_id_foreign');
            $table->string('chave', 44);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compra_referencias');
    }
};
