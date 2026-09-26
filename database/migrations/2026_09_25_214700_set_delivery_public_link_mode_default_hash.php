<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('delivery_configs') || !Schema::hasColumn('delivery_configs', 'public_link_mode')) {
            return;
        }

        // Não converte registros existentes. Apenas define o padrão para novas configurações.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE delivery_configs MODIFY public_link_mode VARCHAR(20) NOT NULL DEFAULT 'hash'"
            );
        }
    }

    public function down(): void
    {
        // Migration evolutiva: não reverte o padrão automaticamente.
        return;
    }
};
