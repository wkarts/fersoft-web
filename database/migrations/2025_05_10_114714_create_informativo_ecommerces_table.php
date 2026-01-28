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
        Schema::create('informativo_ecommerces', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('informativo_ecommerces_empresa_id_foreign');
            $table->string('email', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('informativo_ecommerces');
    }
};
