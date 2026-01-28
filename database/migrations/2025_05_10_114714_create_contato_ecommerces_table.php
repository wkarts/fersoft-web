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
        Schema::create('contato_ecommerces', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('contato_ecommerces_empresa_id_foreign');
            $table->string('nome', 50);
            $table->string('email', 100);
            $table->text('texto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contato_ecommerces');
    }
};
