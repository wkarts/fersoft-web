<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'requisicao_itens';

    public function up(): void
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            if (!Schema::hasColumn($this->table, 'ca_snapshot')) {
                $table->string('ca_snapshot')->nullable()->after('quantidade');
            }

            if (!Schema::hasColumn($this->table, 'fab_snapshot')) {
                $table->string('fab_snapshot')->nullable()->after('ca_snapshot');
            }
        });

        if (!$this->isSqlite()) {
            if (Schema::hasColumn($this->table, 'usuario_id') && !$this->isNullable('usuario_id')) {
                DB::statement("
                    ALTER TABLE `{$this->table}`
                    MODIFY COLUMN `usuario_id` INT UNSIGNED NULL DEFAULT NULL
                ");
            }

            if (Schema::hasColumn($this->table, 'filial_id') && !$this->isNullable('filial_id')) {
                DB::statement("
                    ALTER TABLE `{$this->table}`
                    MODIFY COLUMN `filial_id` INT UNSIGNED NULL DEFAULT NULL
                ");
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        if (!$this->isSqlite()) {
            if (Schema::hasColumn($this->table, 'usuario_id') && $this->isNullable('usuario_id')) {
                $nullCount = (int) DB::table($this->table)->whereNull('usuario_id')->count();

                if ($nullCount > 0) {
                    throw new \RuntimeException(
                        "Rollback bloqueado: a coluna {$this->table}.usuario_id possui {$nullCount} registro(s) nulo(s)."
                    );
                }

                DB::statement("
                    ALTER TABLE `{$this->table}`
                    MODIFY COLUMN `usuario_id` INT UNSIGNED NOT NULL
                ");
            }

            if (Schema::hasColumn($this->table, 'filial_id') && $this->isNullable('filial_id')) {
                $nullCount = (int) DB::table($this->table)->whereNull('filial_id')->count();

                if ($nullCount > 0) {
                    throw new \RuntimeException(
                        "Rollback bloqueado: a coluna {$this->table}.filial_id possui {$nullCount} registro(s) nulo(s)."
                    );
                }

                DB::statement("
                    ALTER TABLE `{$this->table}`
                    MODIFY COLUMN `filial_id` INT UNSIGNED NOT NULL
                ");
            }
        }

        Schema::table($this->table, function (Blueprint $table) {
            $drops = [];

            if (Schema::hasColumn($this->table, 'ca_snapshot')) {
                $drops[] = 'ca_snapshot';
            }

            if (Schema::hasColumn($this->table, 'fab_snapshot')) {
                $drops[] = 'fab_snapshot';
            }

            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }

    private function isNullable(string $column): bool
    {
        $database = DB::getDatabaseName();

        $columnInfo = DB::selectOne(
            'SELECT IS_NULLABLE
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
              LIMIT 1',
            [$database, $this->table, $column]
        );

        return isset($columnInfo->IS_NULLABLE) && strtoupper((string) $columnInfo->IS_NULLABLE) === 'YES';
    }

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }
};
