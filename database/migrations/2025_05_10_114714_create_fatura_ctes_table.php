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
        Schema::create('fatura_ctes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('numero_fatura');
            $table->date('vencimento');
            $table->decimal('valor_total', 10);
            $table->decimal('desconto', 10);
            $table->unsignedInteger('empresa_id')->index('fatura_ctes_empresa_id_foreign');
            $table->unsignedInteger('remetente_id')->index('fatura_ctes_remetente_id_foreign');
            $table->boolean('conta_receber_id')->nullable();
            $table->text('observacao');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fatura_ctes');
    }
};
