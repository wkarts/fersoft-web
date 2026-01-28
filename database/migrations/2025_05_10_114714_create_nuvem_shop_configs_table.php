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
        Schema::create('nuvem_shop_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('nuvem_shop_configs_empresa_id_foreign');
            $table->string('client_id', 10)->default('');
            $table->string('client_secret', 150)->default('');
            $table->string('email', 80)->default('');
            $table->integer('natureza_padrao')->nullable();
            $table->string('forma_pagamento_padrao', 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nuvem_shop_configs');
    }
};
