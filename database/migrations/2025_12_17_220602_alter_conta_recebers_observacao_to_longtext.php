<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('conta_recebers') || !Schema::hasColumn('conta_recebers', 'observacao')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("
            ALTER TABLE `conta_recebers`
            MODIFY `observacao` LONGTEXT
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_unicode_ci
            NULL
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('conta_recebers') || !Schema::hasColumn('conta_recebers', 'observacao')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }
        // ⚠️ Rollback para VARCHAR(100) pode perder dados > 100 caracteres.
        DB::statement("UPDATE `conta_recebers` SET `observacao` = '' WHERE `observacao` IS NULL");
        DB::statement("UPDATE `conta_recebers` SET `observacao` = LEFT(`observacao`, 100)");

        DB::statement("
            ALTER TABLE `conta_recebers`
            MODIFY `observacao` VARCHAR(100)
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_unicode_ci
            NOT NULL
        ");
    }
};
