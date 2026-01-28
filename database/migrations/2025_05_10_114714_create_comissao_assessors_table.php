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
        Schema::create('comissao_assessors', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venda_caixa_id')->nullable()->index('comissao_assessors_venda_caixa_id_foreign');
            $table->decimal('valor', 10);
            $table->boolean('status')->default(false);
            $table->unsignedInteger('assessor_id')->nullable()->index('comissao_assessors_assessor_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comissao_assessors');
    }
};
