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
        Schema::create('pedido_qr_code_clientes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('pedido_qr_code_clientes_empresa_id_foreign');
            $table->unsignedInteger('pedido_id')->nullable()->index('pedido_qr_code_clientes_pedido_id_foreign');
            $table->string('hash', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_qr_code_clientes');
    }
};
