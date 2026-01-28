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
        Schema::create('item_compras', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('compra_id')->index('item_compras_compra_id_foreign');
            $table->unsignedInteger('produto_id')->index('item_compras_produto_id_foreign');
            $table->decimal('quantidade', 16, 7);
            $table->decimal('valor_unitario', 16, 7);
            $table->string('unidade_compra', 10);
            $table->date('validade')->nullable();
            $table->string('cfop_entrada', 4)->default('');
            $table->string('codigo_siad', 10)->default('');
            $table->string('nDI', 30)->nullable();
            $table->date('dDI')->nullable();
            $table->integer('cidade_desembarque_id')->nullable();
            $table->date('dDesemb')->nullable();
            $table->string('tpViaTransp', 2)->nullable();
            $table->decimal('vAFRMM', 12)->nullable();
            $table->string('tpIntermedio', 2)->nullable();
            $table->string('documento', 18)->nullable();
            $table->string('UFTerceiro', 2)->nullable();
            $table->string('cExportador', 30)->nullable();
            $table->string('nAdicao', 10)->nullable();
            $table->string('cFabricante', 20)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_compras');
    }
};
