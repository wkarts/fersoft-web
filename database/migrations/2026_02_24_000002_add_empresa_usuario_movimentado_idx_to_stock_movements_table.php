<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('stock_movements')) {
            return;
        }

        if (!Schema::hasColumn('stock_movements', 'usuario_id')) {
            return;
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['empresa_id', 'usuario_id', 'movimentado_em'], 'stock_movements_empresa_usuario_data_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('stock_movements')) {
            return;
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_empresa_usuario_data_idx');
        });
    }
};
