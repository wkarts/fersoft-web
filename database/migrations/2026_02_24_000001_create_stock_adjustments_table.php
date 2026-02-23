<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('stock_adjustments')) {
            return;
        }

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('filial_id')->nullable();
            $table->unsignedBigInteger('usuario_id');
            $table->date('data_ref');
            $table->text('observacao')->nullable();
            $table->json('itens');
            $table->timestamps();

            $table->index(['empresa_id', 'data_ref'], 'stock_adjustments_empresa_data_idx');
            $table->index(['empresa_id', 'filial_id', 'data_ref'], 'stock_adjustments_empresa_filial_data_idx');
            $table->index(['empresa_id', 'usuario_id', 'data_ref'], 'stock_adjustments_empresa_usuario_data_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('stock_adjustments')) {
            return;
        }

        Schema::dropIfExists('stock_adjustments');
    }
};
