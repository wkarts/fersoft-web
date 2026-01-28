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
        Schema::create('destaque_deliveries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id')->nullable()->index('destaque_deliveries_empresa_id_foreign');
            $table->unsignedInteger('produto_id')->nullable()->index('destaque_deliveries_produto_id_foreign');
            $table->string('img', 30);
            $table->boolean('status');
            $table->integer('ordem');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destaque_deliveries');
    }
};
