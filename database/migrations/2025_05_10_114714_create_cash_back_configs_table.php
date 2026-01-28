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
        Schema::create('cash_back_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('cash_back_configs_empresa_id_foreign');
            $table->decimal('valor_percentual', 5);
            $table->integer('dias_expiracao');
            $table->decimal('valor_minimo_venda', 10);
            $table->decimal('percentual_maximo_venda', 10);
            $table->text('mensagem_padrao_whatsapp');
            $table->text('mensagem_automatica_5_dias');
            $table->text('mensagem_automatica_1_dia');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_back_configs');
    }
};
