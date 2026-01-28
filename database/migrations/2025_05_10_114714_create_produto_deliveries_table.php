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
        Schema::create('produto_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('produto_deliveries_empresa_id_foreign');
            $table->unsignedInteger('produto_id')->index('produto_deliveries_produto_id_foreign');
            $table->unsignedInteger('categoria_id')->index('produto_deliveries_categoria_id_foreign');
            $table->string('descricao_curta', 50);
            $table->string('descricao');
            $table->string('ingredientes');
            $table->string('referencia', 12);
            $table->decimal('valor', 10);
            $table->decimal('valor_anterior', 10);
            $table->boolean('status');
            $table->integer('destaque');
            $table->integer('limite_diario');
            $table->boolean('tem_adicionais')->default(false);
            $table->enum('tipo', ['simples', 'variavel']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produto_deliveries');
    }
};
