<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeOptionalColumnsNullableInRequisicoesTable extends Migration
{
    private string $table = 'requisicoes';

    /**
     * Colunas opcionais da requisição.
     * empresa_id permanece obrigatório.
     *
     * @var array<int, string>
     */
    private array $optionalColumns = [
        'filial_id',
        'funcionario_id',
        'responsavel_id',
        'usuario_id',
    ];

    private ?string $driver = null;

    public function up(): void
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        // SQLite não suporta MODIFY COLUMN de forma nativa.
        // Mantemos a migration segura em ambiente de testes (sqlite in-memory).
        if ($this->isSqlite()) {
            return;
        }

        foreach ($this->optionalColumns as $column) {
            if (!Schema::hasColumn($this->table, $column)) {
                continue;
            }

            if ($this->isNullable($column)) {
                continue;
            }

            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY COLUMN `%s` INT UNSIGNED NULL',
                $this->table,
                $column
            ));
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        // Mesmo motivo do up(): rollback estrutural no SQLite exigiria recriação de tabela.
        if ($this->isSqlite()) {
            return;
        }

        foreach ($this->optionalColumns as $column) {
            if (!Schema::hasColumn($this->table, $column)) {
                continue;
            }

            if (!$this->isNullable($column)) {
                continue;
            }

            $nullCount = (int) DB::table($this->table)->whereNull($column)->count();

            if ($nullCount > 0) {
                throw new \RuntimeException(sprintf(
                    'Rollback bloqueado: a coluna %s.%s possui %d registro(s) nulo(s). Preencha os dados antes do down().',
                    $this->table,
                    $column,
                    $nullCount
                ));
            }

            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY COLUMN `%s` INT UNSIGNED NOT NULL',
                $this->table,
                $column
            ));
        }
    }

    private function isNullable(string $column): bool
    {
        if ($this->isSqlite()) {
            $columns = DB::select(sprintf('PRAGMA table_info("%s")', $this->table));

            foreach ($columns as $columnInfo) {
                if (($columnInfo->name ?? null) === $column) {
                    return ((int) ($columnInfo->notnull ?? 1)) === 0;
                }
            }

            return false;
        }

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

        return isset($columnInfo->IS_NULLABLE) && $columnInfo->IS_NULLABLE === 'YES';
    }

    private function isSqlite(): bool
    {
        if ($this->driver === null) {
            $this->driver = DB::connection()->getDriverName();
        }

        return $this->driver === 'sqlite';
    }
}
