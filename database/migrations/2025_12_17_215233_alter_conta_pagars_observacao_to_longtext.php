<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('conta_pagars') || !Schema::hasColumn('conta_pagars', 'observacao')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }
        // Converte para LONGTEXT (utf8mb4) sem perder dados existentes
        DB::statement("
            ALTER TABLE `conta_pagars`
            MODIFY `observacao` LONGTEXT
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_unicode_ci
            NULL
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('conta_pagars') || !Schema::hasColumn('conta_pagars', 'observacao')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }
        // ⚠️ Rollback para VARCHAR(100) pode perder dados > 100 caracteres.
        // Para evitar erro em modo STRICT e não deixar NULL:
        DB::statement("UPDATE `conta_pagars` SET `observacao` = '' WHERE `observacao` IS NULL");
        DB::statement("UPDATE `conta_pagars` SET `observacao` = LEFT(`observacao`, 100)");

        DB::statement("
            ALTER TABLE `conta_pagars`
            MODIFY `observacao` VARCHAR(100)
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_unicode_ci
            NOT NULL DEFAULT ''
        ");
    }
};
