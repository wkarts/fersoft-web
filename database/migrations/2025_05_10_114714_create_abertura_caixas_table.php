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
        if (Schema::hasTable('abertura_caixas')) {
            return;
        }

        Schema::create('abertura_caixas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('abertura_caixas_empresa_id_foreign');
            $table->unsignedInteger('usuario_id')->index('abertura_caixas_usuario_id_foreign');
            $table->timestamp('data_registro')->useCurrent();
            $table->decimal('valor', 10);
            $table->decimal('valor_dinheiro_caixa', 10)->default(0);
            $table->integer('ultima_venda_nfe')->default(0);
            $table->integer('primeira_venda_nfe')->default(0);
            $table->integer('ultima_venda_nfce')->default(0);
            $table->integer('primeira_venda_nfce')->default(0);
            $table->boolean('status')->default(false);
            $table->unsignedInteger('filial_id')->nullable()->index('abertura_caixas_filial_id_foreign');
            $table->integer('conta_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abertura_caixas');
    }
};
