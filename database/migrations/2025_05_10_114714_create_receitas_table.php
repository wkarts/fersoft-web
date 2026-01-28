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
        Schema::create('receitas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produto_id')->nullable()->index('receitas_produto_id_foreign');
            $table->string('descricao');
            $table->double('rendimento');
            $table->decimal('valor_custo', 10);
            $table->integer('tempo_preparo');
            $table->boolean('pizza');
            $table->integer('pedacos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receitas');
    }
};
