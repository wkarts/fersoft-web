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
        Schema::create('produto_ibpts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produto_id')->index('produto_ibpts_produto_id_foreign');
            $table->string('codigo');
            $table->string('uf', 2);
            $table->string('descricao', 100);
            $table->decimal('nacional', 5);
            $table->decimal('estadual', 5);
            $table->decimal('importado', 5);
            $table->decimal('municipal', 5);
            $table->string('vigencia_inicio', 10);
            $table->string('vigencia_fim', 10);
            $table->string('chave', 10);
            $table->string('versao', 10);
            $table->string('fonte', 40);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produto_ibpts');
    }
};
