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

        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'usuario_id')) {
                $table->unsignedBigInteger('usuario_id')->nullable()->after('filial_id');
                $table->index(['empresa_id', 'usuario_id'], 'stock_movements_empresa_usuario_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('stock_movements')) {
            return;
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            if (Schema::hasColumn('stock_movements', 'usuario_id')) {
                $table->dropIndex('stock_movements_empresa_usuario_idx');
                $table->dropColumn('usuario_id');
            }
        });
    }
};
