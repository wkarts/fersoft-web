<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conta_pagars', function (Blueprint $table) {
            if (!Schema::hasColumn('conta_pagars', 'juros')) {
                $table->decimal('juros', 10, 2)->default(0.00)->nullable(false);
            }

            if (!Schema::hasColumn('conta_pagars', 'multa')) {
                $table->decimal('multa', 10, 2)->default(0.00)->nullable(false);
            }

            if (!Schema::hasColumn('conta_pagars', 'data_emissao')) {
                $table->date('data_emissao')->nullable();
            }
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            if (Schema::hasColumn('conta_pagars', 'juros')) {
                DB::statement("
                    ALTER TABLE conta_pagars
                    MODIFY juros DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER observacao
                ");
            }

            if (Schema::hasColumn('conta_pagars', 'multa')) {
                DB::statement("
                    ALTER TABLE conta_pagars
                    MODIFY multa DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER juros
                ");
            }
        }
    }

    public function down(): void
    {
        Schema::table('conta_pagars', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('conta_pagars', 'data_emissao')) {
                $columnsToDrop[] = 'data_emissao';
            }

            if (Schema::hasColumn('conta_pagars', 'multa')) {
                $columnsToDrop[] = 'multa';
            }

            if (Schema::hasColumn('conta_pagars', 'juros')) {
                $columnsToDrop[] = 'juros';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
