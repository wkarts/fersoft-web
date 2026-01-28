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
        Schema::create('receita_ctes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cte_id')->index('receita_ctes_cte_id_foreign');
            $table->decimal('valor', 10);
            $table->string('descricao', 50);
            $table->timestamp('data_registro')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receita_ctes');
    }
};
