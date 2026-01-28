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
        Schema::create('cupom_desconto_ecommerces', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('cupom_desconto_ecommerces_empresa_id_foreign');
            $table->string('descricao', 100);
            $table->decimal('valor', 10);
            $table->decimal('valor_minimo_pedido', 10);
            $table->boolean('status')->default(true);
            $table->string('codigo', 6);
            $table->enum('tipo', ['percentual', 'fixo']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cupom_desconto_ecommerces');
    }
};
