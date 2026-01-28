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
        Schema::create('forma_pagamentos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->nullable()->index('forma_pagamentos_empresa_id_foreign');
            $table->string('nome', 40);
            $table->string('chave', 30);
            $table->decimal('taxa', 10)->default(0);
            $table->string('tipo_taxa', 5)->default('perc');
            $table->integer('prazo_dias');
            $table->boolean('status');
            $table->string('infos', 100)->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forma_pagamentos');
    }
};
