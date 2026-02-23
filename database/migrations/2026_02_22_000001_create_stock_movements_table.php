<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('stock_movements')) {
            return;
        }

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('filial_id')->nullable();
            $table->unsignedBigInteger('produto_id');
            $table->string('contexto', 20); // ERP | PESAGEM
            $table->string('tipo', 10); // entrada | saida
            $table->decimal('quantidade', 16, 4);
            $table->decimal('custo_unitario', 16, 6)->nullable();
            $table->decimal('valor_total', 16, 2)->nullable();
            $table->string('origem_tipo', 40)->nullable();
            $table->unsignedBigInteger('origem_id')->nullable();
            $table->string('idempotency_key', 190);
            $table->timestamp('movimentado_em');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('idempotency_key', 'stock_movements_idempotency_unique');
            $table->index(['empresa_id', 'filial_id', 'contexto', 'movimentado_em'], 'stock_movements_lookup_idx');
            $table->index(['empresa_id', 'produto_id', 'contexto', 'movimentado_em'], 'stock_movements_produto_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('stock_movements')) {
            return;
        }

        Schema::dropIfExists('stock_movements');
    }
};

