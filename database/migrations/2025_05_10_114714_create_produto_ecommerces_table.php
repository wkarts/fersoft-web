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
        Schema::create('produto_ecommerces', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('produto_ecommerces_empresa_id_foreign');
            $table->unsignedInteger('produto_id')->index('produto_ecommerces_produto_id_foreign');
            $table->unsignedInteger('categoria_id')->index('produto_ecommerces_categoria_id_foreign');
            $table->integer('sub_categoria_id')->default(0);
            $table->text('descricao');
            $table->boolean('controlar_estoque');
            $table->boolean('status');
            $table->boolean('destaque');
            $table->string('cep', 9)->default('');
            $table->decimal('valor', 10);
            $table->integer('percentual_desconto_view')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produto_ecommerces');
    }
};
