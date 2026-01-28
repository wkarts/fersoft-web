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
        Schema::create('bairro_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cidade_id')->index('bairro_deliveries_cidade_id_foreign');
            $table->string('nome', 20);
            $table->decimal('valor_entrega', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bairro_deliveries');
    }
};
