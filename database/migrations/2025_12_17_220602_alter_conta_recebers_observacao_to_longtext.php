<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
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
