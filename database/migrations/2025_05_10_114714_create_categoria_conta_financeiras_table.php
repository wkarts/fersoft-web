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
        Schema::create('categoria_conta_financeiras', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('categoria_conta_financeiras_empresa_id_foreign');
            $table->string('nome', 60);
            $table->enum('tipo', ['receita', 'despesa']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categoria_conta_financeiras');
    }
};
