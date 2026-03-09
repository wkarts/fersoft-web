<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('conta_recebers', 'desconto')) {
            Schema::table('conta_recebers', function (Blueprint $table) {
                $table->decimal('desconto', 10, 2)->default(0.00)->nullable(false);
            });
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' && Schema::hasColumn('conta_recebers', 'desconto')) {
            DB::statement("
                ALTER TABLE conta_recebers
                MODIFY desconto DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER multa
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('conta_recebers', 'desconto')) {
            Schema::table('conta_recebers', function (Blueprint $table) {
                $table->dropColumn('desconto');
            });
        }
    }
};
