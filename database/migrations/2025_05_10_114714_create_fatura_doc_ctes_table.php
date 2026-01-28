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
        Schema::create('fatura_doc_ctes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('fatura_id')->index('fatura_doc_ctes_fatura_id_foreign');
            $table->unsignedInteger('cte_id')->index('fatura_doc_ctes_cte_id_foreign');
            $table->string('unidade', 20)->nullable();
            $table->string('cte_numero', 20)->nullable();
            $table->string('chave_nfe', 44)->nullable();
            $table->decimal('valor_mercadoria', 10);
            $table->decimal('peso', 10);
            $table->decimal('frete', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fatura_doc_ctes');
    }
};
