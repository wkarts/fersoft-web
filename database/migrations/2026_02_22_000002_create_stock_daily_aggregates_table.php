<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('stock_daily_aggregates')) {
            return;
        }

        Schema::create('stock_daily_aggregates', function (Blueprint $table) {
            $table->id();
            $table->date('data_ref');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('filial_id')->nullable();
            $table->unsignedBigInteger('produto_id');
            $table->string('contexto', 20); // ERP | PESAGEM
            $table->decimal('entrada', 16, 4)->default(0);
            $table->decimal('saida', 16, 4)->default(0);
            $table->decimal('saldo', 16, 4)->default(0);
            $table->decimal('valor_entrada', 16, 2)->default(0);
            $table->decimal('valor_saida', 16, 2)->default(0);
            $table->decimal('custo_medio', 16, 6)->nullable();
            $table->timestamps();

            $table->unique(['data_ref', 'empresa_id', 'filial_id', 'produto_id', 'contexto'], 'stock_daily_aggregates_uniq');
            $table->index(['empresa_id', 'filial_id', 'contexto', 'data_ref'], 'stock_daily_aggregates_lookup_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('stock_daily_aggregates')) {
            return;
        }

        Schema::dropIfExists('stock_daily_aggregates');
    }
};
