<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('conta_pagars')) {
            return;
        }

        $this->applyColumnDefinition('conta_pagars', 'numero_nota_fiscal', 'VARCHAR(15) NULL DEFAULT \'0\'');
    }

    public function down()
    {
        // Migration exclusivamente evolutiva.
        return;
    }


    private function applyColumnDefinition(string $tableName, string $columnName, string $definition): void
    {
        if (!$this->isMysql() || !Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        $current = $this->columnInfo($tableName, $columnName);

        if (!$current || $this->columnDefinitionEquivalent($current, $definition)) {
            return;
        }

        if ($this->columnHasForeignDependency($tableName, $columnName)) {
            $this->warnForwardOnly(
                "Alteração ignorada em {$tableName}.{$columnName}: a coluna participa de relacionamento existente e a atualização exigiria remover a constraint."
            );
            return;
        }

        if (!$this->columnDataFitsDefinition($tableName, $columnName, $definition, $current)) {
            $this->warnForwardOnly(
                "Alteração ignorada em {$tableName}.{$columnName}: os dados atuais não são compatíveis com a definição solicitada."
            );
            return;
        }

        $this->executeSafeStatement(
            sprintf(
                'ALTER TABLE %s MODIFY COLUMN %s %s',
                $this->quoteIdentifier($tableName),
                $this->quoteIdentifier($columnName),
                $definition
            ),
            "{$tableName}.{$columnName}"
        );
    }

    private function ensureTableCollation(string $tableName, string $collation): void
    {
        if (
            !$this->isMysql()
            || !Schema::hasTable($tableName)
            || !preg_match('/^[A-Za-z0-9_]+$/', $collation)
        ) {
            return;
        }

        $current = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->value('TABLE_COLLATION');

        if (strcasecmp((string) $current, $collation) === 0) {
            return;
        }

        $this->executeSafeStatement(
            sprintf(
                'ALTER TABLE %s DEFAULT CHARACTER SET utf8mb4 COLLATE %s',
                $this->quoteIdentifier($tableName),
                $collation
            ),
            "{$tableName}.collation"
        );
    }

    private function columnDefinitionEquivalent(object $current, string $definition): bool
    {
        $desired = $this->extractType($definition);

        if ($desired === null) {
            return false;
        }

        $desiredType = strtolower($desired['base']);
        if ($desired['args_raw'] !== '') {
            $desiredType .= '(' . preg_replace('/\s+/', '', $desired['args_raw']) . ')';
        }
        if ($desired['unsigned']) {
            $desiredType .= ' unsigned';
        }

        $currentType = strtolower(preg_replace('/\s+/', ' ', trim((string) $current->column_type)) ?? '');

        if ($currentType !== $desiredType) {
            return false;
        }

        $desiredNullable = !preg_match('/\bNOT\s+NULL\b/i', $definition);
        $currentNullable = strtoupper((string) $current->is_nullable) === 'YES';

        if ($currentNullable !== $desiredNullable) {
            return false;
        }

        $desiredDefault = $this->extractDefault($definition, $desiredNullable);
        $currentDefault = $this->normalizeDefault($current->column_default);

        if ($desiredDefault !== $currentDefault) {
            return false;
        }

        $desiredComment = $this->extractComment($definition);

        return $desiredComment === (string) ($current->column_comment ?? '');
    }

    private function columnDataFitsDefinition(
        string $tableName,
        string $columnName,
        string $definition,
        object $current
    ): bool {
        $desiredNullable = !preg_match('/\bNOT\s+NULL\b/i', $definition);

        if (!$desiredNullable && $this->hasNullValues($tableName, $columnName)) {
            return false;
        }

        $type = $this->extractType($definition);

        if ($type === null) {
            return false;
        }

        $quotedTable = $this->quoteIdentifier($tableName);
        $quotedColumn = $this->quoteIdentifier($columnName);
        $base = strtolower($type['base']);
        $currentBase = strtolower((string) $current->data_type);

        if (in_array($base, ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'], true)) {
            if (in_array($currentBase, ['varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext'], true)) {
                $invalidSql = sprintf(
                    "SELECT 1 FROM %s WHERE %s IS NOT NULL AND TRIM(%s) NOT REGEXP '^[+-]?[0-9]+$' LIMIT 1",
                    $quotedTable,
                    $quotedColumn,
                    $quotedColumn
                );

                if ($this->queryHasRows($invalidSql)) {
                    return false;
                }
            }

            [$min, $max] = $this->integerRange($base, (bool) $type['unsigned']);

            return !$this->queryHasRows(
                "SELECT 1 FROM {$quotedTable}
                 WHERE {$quotedColumn} IS NOT NULL
                   AND (
                       CAST({$quotedColumn} AS DECIMAL(65, 0)) < {$min}
                       OR CAST({$quotedColumn} AS DECIMAL(65, 0)) > {$max}
                   )
                 LIMIT 1"
            );
        }

        if (in_array($base, ['decimal', 'numeric'], true) && count($type['args']) >= 2) {
            if (in_array($currentBase, ['varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext'], true)) {
                $invalidSql = sprintf(
                    "SELECT 1 FROM %s WHERE %s IS NOT NULL AND TRIM(%s) NOT REGEXP '^[+-]?[0-9]+([.][0-9]+)?$' LIMIT 1",
                    $quotedTable,
                    $quotedColumn,
                    $quotedColumn
                );

                if ($this->queryHasRows($invalidSql)) {
                    return false;
                }
            }

            $precision = (int) $type['args'][0];
            $scale = (int) $type['args'][1];
            $integerDigits = max(0, $precision - $scale);
            $limit = $integerDigits > 0 ? str_repeat('9', $integerDigits) : '0';
            $fraction = $scale > 0 ? '.' . str_repeat('9', $scale) : '';
            $max = $limit . $fraction;
            $min = $type['unsigned'] ? '0' : '-' . $max;

            return !$this->queryHasRows(
                "SELECT 1 FROM {$quotedTable}
                 WHERE {$quotedColumn} IS NOT NULL
                   AND (
                       CAST({$quotedColumn} AS DECIMAL(65, 30)) < {$min}
                       OR CAST({$quotedColumn} AS DECIMAL(65, 30)) > {$max}
                       OR CAST({$quotedColumn} AS DECIMAL(65, 30))
                          <> ROUND(CAST({$quotedColumn} AS DECIMAL(65, 30)), {$scale})
                   )
                 LIMIT 1"
            );
        }

        if (in_array($base, ['varchar', 'char', 'binary', 'varbinary'], true) && isset($type['args'][0])) {
            $length = (int) $type['args'][0];

            return !$this->queryHasRows(
                "SELECT 1 FROM {$quotedTable}
                 WHERE {$quotedColumn} IS NOT NULL
                   AND CHAR_LENGTH({$quotedColumn}) > {$length}
                 LIMIT 1"
            );
        }

        if ($base === 'enum' && $type['enum_values'] !== []) {
            $allowed = implode(
                ', ',
                array_map(fn (string $value): string => $this->quoteValue($value), $type['enum_values'])
            );

            return !$this->queryHasRows(
                "SELECT 1 FROM {$quotedTable}
                 WHERE {$quotedColumn} IS NOT NULL
                   AND {$quotedColumn} NOT IN ({$allowed})
                 LIMIT 1"
            );
        }

        $compatibleFamilies = [
            ['varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext', 'enum'],
            ['date', 'datetime', 'timestamp'],
            ['time'],
            ['float', 'double', 'real', 'decimal', 'numeric'],
            ['blob', 'tinyblob', 'mediumblob', 'longblob', 'binary', 'varbinary'],
        ];

        foreach ($compatibleFamilies as $family) {
            if (in_array($base, $family, true) && in_array($currentBase, $family, true)) {
                return true;
            }
        }

        return $base === $currentBase
            || (in_array($currentBase, ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'], true)
                && in_array($base, ['varchar', 'char', 'text'], true));
    }

    private function columnHasForeignDependency(string $tableName, string $columnName): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where(function ($query) use ($tableName, $columnName): void {
                $query->where(function ($local) use ($tableName, $columnName): void {
                    $local->where('TABLE_NAME', $tableName)
                        ->where('COLUMN_NAME', $columnName)
                        ->whereNotNull('REFERENCED_TABLE_NAME');
                })->orWhere(function ($referenced) use ($tableName, $columnName): void {
                    $referenced->where('REFERENCED_TABLE_NAME', $tableName)
                        ->where('REFERENCED_COLUMN_NAME', $columnName);
                });
            })
            ->exists();
    }

    private function columnInfo(string $tableName, string $columnName): ?object
    {
        return DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->select([
                'COLUMN_TYPE as column_type',
                'DATA_TYPE as data_type',
                'IS_NULLABLE as is_nullable',
                'COLUMN_DEFAULT as column_default',
                'COLUMN_COMMENT as column_comment',
            ])
            ->first();
    }

    private function extractType(string $definition): ?array
    {
        if (!preg_match('/^\s*([A-Za-z]+)(?:\s*\((.*?)\))?(?:\s+(UNSIGNED))?/i', $definition, $matches)) {
            return null;
        }

        $base = strtolower($matches[1]);
        $argsRaw = trim((string) ($matches[2] ?? ''));
        $args = [];
        $enumValues = [];

        if ($argsRaw !== '') {
            if ($base === 'enum') {
                preg_match_all("/'((?:\\\\.|[^'])*)'/", $argsRaw, $values);
                $enumValues = array_map(
                    static fn (string $value): string => stripcslashes($value),
                    $values[1] ?? []
                );
            } else {
                $args = array_map('trim', explode(',', $argsRaw));
            }
        }

        return [
            'base' => $base,
            'args_raw' => $argsRaw,
            'args' => $args,
            'unsigned' => isset($matches[3]) && $matches[3] !== '',
            'enum_values' => $enumValues,
        ];
    }

    private function extractDefault(string $definition, bool $nullable): mixed
    {
        if (!preg_match(
            '/\bDEFAULT\s+((?:NULL)|(?:CURRENT_TIMESTAMP(?:\(\d+\))?)|(?:\'(?:\\\\.|[^\'])*\')|(?:"(?:\\\\.|[^"])*")|(?:[^\s,]+))/i',
            $definition,
            $matches
        )) {
            return $nullable ? null : '__NO_DEFAULT__';
        }

        $value = trim($matches[1]);

        if (strcasecmp($value, 'NULL') === 0) {
            return null;
        }

        if (
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
            || (str_starts_with($value, '"') && str_ends_with($value, '"'))
        ) {
            $value = substr($value, 1, -1);
        }

        return $this->normalizeDefault($value);
    }

    private function normalizeDefault(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $stringValue = (string) $value;

        if (is_numeric($stringValue)) {
            $normalized = rtrim(rtrim($stringValue, '0'), '.');

            return $normalized === '' || $normalized === '-0' ? '0' : $normalized;
        }

        return strtoupper($stringValue) === 'CURRENT_TIMESTAMP'
            ? 'CURRENT_TIMESTAMP'
            : $stringValue;
    }

    private function extractComment(string $definition): string
    {
        if (!preg_match("/\bCOMMENT\s+'((?:\\\\.|[^'])*)'/i", $definition, $matches)) {
            return '';
        }

        return stripcslashes($matches[1]);
    }

    private function integerRange(string $base, bool $unsigned): array
    {
        $bits = match ($base) {
            'tinyint' => 8,
            'smallint' => 16,
            'mediumint' => 24,
            'bigint' => 64,
            default => 32,
        };

        if ($bits === 64) {
            return $unsigned
                ? ['0', '18446744073709551615']
                : ['-9223372036854775808', '9223372036854775807'];
        }

        if ($unsigned) {
            return ['0', (string) ((2 ** $bits) - 1)];
        }

        return [
            (string) (-(2 ** ($bits - 1))),
            (string) ((2 ** ($bits - 1)) - 1),
        ];
    }

    private function hasNullValues(string $tableName, string $columnName): bool
    {
        return $this->queryHasRows(
            sprintf(
                'SELECT 1 FROM %s WHERE %s IS NULL LIMIT 1',
                $this->quoteIdentifier($tableName),
                $this->quoteIdentifier($columnName)
            )
        );
    }

    private function queryHasRows(string $sql): bool
    {
        return DB::selectOne($sql) !== null;
    }

    private function executeSafeStatement(string $sql, string $context): void
    {
        try {
            DB::statement($sql);
        } catch (QueryException $exception) {
            $driverCode = (int) ($exception->errorInfo[1] ?? 0);
            $safeCodes = [1062, 1138, 1265, 1292, 1366, 1832, 3780];

            if (!in_array($driverCode, $safeCodes, true)) {
                throw $exception;
            }

            $this->warnForwardOnly(
                "Atualização ignorada em {$context}: MySQL {$driverCode} - {$exception->getMessage()}"
            );
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function quoteValue(string $value): string
    {
        return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $value) . "'";
    }

    private function warnForwardOnly(string $message): void
    {
        logger()->warning('[migration-forward-only] ' . $message);
    }

    private function isMysql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

};
