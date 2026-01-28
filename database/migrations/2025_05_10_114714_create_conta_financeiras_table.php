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
        Schema::create('conta_financeiras', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('conta_financeiras_empresa_id_foreign');
            $table->unsignedInteger('categoria_id')->index('conta_financeiras_categoria_id_foreign');
            $table->unsignedInteger('sub_categoria_id')->nullable()->index('conta_financeiras_sub_categoria_id_foreign');
            $table->string('nome', 60);
            $table->decimal('saldo_inicial', 20, 7);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conta_financeiras');
    }
};
