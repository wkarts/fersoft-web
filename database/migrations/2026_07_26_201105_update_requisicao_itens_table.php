<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('requisicao_itens')) {
            return;
        }

        $this->addForeignIfMissing('requisicao_itens', 'requisicao_itens_filial_id_foreign', ['filial_id'], 'filials', ['id'], 'cascade', null);
    }

    public function down()
    {
        // Migration somente evolutiva.
        return;
    }

    private function applyColumnDefinition(
        string $tableName,
        string $columnName,
        string $definition,
        ?string $afterColumn = null,
        bool $first = false
    ): void {
        if (!$this->isMysql() || !Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        /*
         * Nunca altere diretamente uma coluna que já participa de uma FK.
         * Mesmo uma mudança aparentemente simples de NULL/UNSIGNED/posição
         * pode provocar os erros MySQL 1832 ou 3780.
         */
        if ($this->columnParticipatesInForeignKey($tableName, $columnName)) {
            $this->warnForwardOnly(
                "Alteração ignorada em {$tableName}.{$columnName}: a coluna participa de uma chave estrangeira ativa."
            );
            return;
        }

        /*
         * Forward-only: jamais converta um inteiro UNSIGNED existente para
         * uma definição signed. Essa alteração quebra compatibilidade com
         * IDs tenant e demais referências.
         */
        $currentColumn = $this->columnInfo($tableName, $columnName);
        if ($currentColumn && $this->wouldRemoveUnsignedInteger((string) $currentColumn->column_type, $definition)) {
            $this->warnForwardOnly(
                "Alteração ignorada em {$tableName}.{$columnName}: a definição solicitada removeria UNSIGNED."
            );
            return;
        }

        if (!$this->columnDataFitsDefinition($tableName, $columnName, $definition)) {
            $this->warnForwardOnly("Alteração ignorada em {$tableName}.{$columnName}: os dados atuais não cabem com segurança na definição solicitada.");
            return;
        }

        $positionSql = '';
        if ($first) {
            $positionSql = ' FIRST';
        } elseif ($afterColumn !== null && Schema::hasColumn($tableName, $afterColumn)) {
            $positionSql = ' AFTER ' . $this->quoteIdentifier($afterColumn);
        }

        DB::statement(sprintf(
            'ALTER TABLE %s MODIFY COLUMN %s %s%s',
            $this->quoteIdentifier($tableName),
            $this->quoteIdentifier($columnName),
            $definition,
            $positionSql
        ));
    }

    private function ensureTableCollation(string $tableName, string $collation): void
    {
        if (!$this->isMysql() || !Schema::hasTable($tableName)) {
            return;
        }

        $current = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->value('TABLE_COLLATION');

        if (strcasecmp((string) $current, $collation) === 0) {
            return;
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $collation)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE %s COLLATE %s',
            $this->quoteIdentifier($tableName),
            $collation
        ));
    }

    private function addIndexIfMissing(string $tableName, string $indexName, array $columns, bool $unique = false): void
    {
        if (!Schema::hasTable($tableName) || !$this->allColumnsExist($tableName, $columns)) {
            return;
        }

        $named = $this->indexDefinition($tableName, $indexName);
        if ($named !== null) {
            if ($named['columns'] !== $columns || $named['unique'] !== $unique) {
                $this->warnForwardOnly("Índice {$indexName} já existe em {$tableName} com outra definição; a substituição exigiria remoção prévia.");
            }
            return;
        }

        if ($this->equivalentIndexExists($tableName, $columns, $unique)) {
            return;
        }

        if ($unique && $this->hasDuplicateValues($tableName, $columns)) {
            $this->warnForwardOnly("Índice único {$indexName} ignorado: existem valores duplicados em {$tableName}.");
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName, $unique): void {
            if ($unique) {
                $table->unique($columns, $indexName);
            } else {
                $table->index($columns, $indexName);
            }
        });
    }

    private function addPrimaryKeyIfMissing(string $tableName, array $columns): void
    {
        if ($this->isSqlite() || !Schema::hasTable($tableName) || !$this->allColumnsExist($tableName, $columns)) {
            return;
        }

        $current = $this->primaryKeyColumns($tableName);
        if ($current !== []) {
            if ($current !== $columns) {
                $this->warnForwardOnly("A tabela {$tableName} já possui outra chave primária; a substituição exigiria remoção prévia.");
            }
            return;
        }

        if ($this->hasNullValues($tableName, $columns) || $this->hasDuplicateValues($tableName, $columns, false)) {
            $this->warnForwardOnly("Chave primária ignorada em {$tableName}: existem valores nulos ou duplicados.");
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns): void {
            $table->primary($columns);
        });
    }

    private function ensureAutoIncrement(string $tableName, string $columnName): void
    {
        if (!$this->isMysql() || !Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        $column = $this->columnInfo($tableName, $columnName);
        if (!$column || str_contains(strtolower((string) $column->extra), 'auto_increment')) {
            return;
        }

        if (!$this->columnStartsAnyIndex($tableName, $columnName)) {
            $this->warnForwardOnly("AUTO_INCREMENT ignorado em {$tableName}.{$columnName}: a coluna precisa iniciar uma chave ou índice.");
            return;
        }

        $definition = $this->renderCurrentColumnDefinition($column, true);
        DB::statement(sprintf(
            'ALTER TABLE %s MODIFY COLUMN %s %s',
            $this->quoteIdentifier($tableName),
            $this->quoteIdentifier($columnName),
            $definition
        ));
    }

    private function addForeignIfMissing(
        string $tableName,
        string $foreignName,
        array $columns,
        string $referenceTable,
        array $referenceColumns,
        ?string $onDelete = null,
        ?string $onUpdate = null
    ): void {
        if (
            $this->isSqlite()
            || !Schema::hasTable($tableName)
            || !Schema::hasTable($referenceTable)
            || count($columns) !== count($referenceColumns)
            || !$this->allColumnsExist($tableName, $columns)
            || !$this->allColumnsExist($referenceTable, $referenceColumns)
        ) {
            return;
        }

        $named = $this->foreignDefinition($tableName, $foreignName);
        if ($named !== null) {
            $expected = [
                'columns' => $columns,
                'reference_table' => $referenceTable,
                'reference_columns' => $referenceColumns,
                'on_delete' => $this->normalizeRule($onDelete),
                'on_update' => $this->normalizeRule($onUpdate),
            ];
            if ($named !== $expected) {
                $this->warnForwardOnly("Relacionamento {$foreignName} já existe com outra definição; a substituição exigiria remoção prévia.");
            }
            return;
        }

        if ($this->equivalentForeignExists($tableName, $columns, $referenceTable, $referenceColumns, $onDelete, $onUpdate)) {
            return;
        }

        foreach ($columns as $position => $column) {
            $referenceColumn = $referenceColumns[$position];
            if (!$this->normalizeForeignColumnIfSafe($tableName, $column, $referenceTable, $referenceColumn, $onDelete)) {
                $this->warnForwardOnly("Relacionamento {$foreignName} ignorado: tipos incompatíveis em {$tableName}.{$column}.");
                return;
            }
            if ($this->hasOrphanForeignValues($tableName, $column, $referenceTable, $referenceColumn)) {
                $this->warnForwardOnly("Relacionamento {$foreignName} ignorado: existem registros órfãos em {$tableName}.{$column}.");
                return;
            }
        }

        Schema::table($tableName, function (Blueprint $table) use (
            $foreignName,
            $columns,
            $referenceTable,
            $referenceColumns,
            $onDelete,
            $onUpdate
        ): void {
            $foreign = $table->foreign($columns, $foreignName)
                ->references($referenceColumns)
                ->on($referenceTable);

            if ($onDelete !== null) {
                $foreign->onDelete($onDelete);
            }
            if ($onUpdate !== null) {
                $foreign->onUpdate($onUpdate);
            }
        });
    }

    private function normalizeForeignColumnIfSafe(
        string $tableName,
        string $columnName,
        string $referenceTable,
        string $referenceColumn,
        ?string $onDelete
    ): bool {
        /*
         * Se a coluna já participa de qualquer FK, não tente normalizá-la
         * por ALTER TABLE. Apenas aceite quando o tipo atual já for compatível.
         */
        if ($this->columnParticipatesInForeignKey($tableName, $columnName)) {
            $existingLocal = $this->columnInfo($tableName, $columnName);
            $existingReference = $this->columnInfo($referenceTable, $referenceColumn);

            if (!$existingLocal || !$existingReference) {
                return false;
            }

            return $this->normalizeType((string) $existingLocal->column_type)
                === $this->normalizeType((string) $existingReference->column_type);
        }

        $local = $this->columnInfo($tableName, $columnName);
        $reference = $this->columnInfo($referenceTable, $referenceColumn);
        if (!$local || !$reference) {
            return false;
        }

        $nullable = strtoupper((string) $local->is_nullable) === 'YES' || $this->normalizeRule($onDelete) === 'SET NULL';
        $definition = strtoupper((string) $reference->column_type)
            . ($nullable ? ' NULL' : ' NOT NULL')
            . $this->renderDefaultClause($local, $nullable)
            . $this->renderCommentClause((string) $local->column_comment);

        if (!$this->columnDataFitsDefinition($tableName, $columnName, $definition)) {
            return false;
        }

        if ($this->normalizeType((string) $local->column_type) !== $this->normalizeType((string) $reference->column_type)
            || (strtoupper((string) $local->is_nullable) === 'YES') !== $nullable) {
            DB::statement(sprintf(
                'ALTER TABLE %s MODIFY COLUMN %s %s',
                $this->quoteIdentifier($tableName),
                $this->quoteIdentifier($columnName),
                $definition
            ));
        }

        return true;
    }

    private function columnDataFitsDefinition(string $tableName, string $columnName, string $definition): bool
    {
        $desiredNullable = !preg_match('/\\bNOT\\s+NULL\\b/i', $definition);
        if (!$desiredNullable && $this->hasNullValues($tableName, [$columnName])) {
            return false;
        }

        $type = $this->extractType($definition);
        if ($type === null) {
            return false;
        }

        $quotedTable = $this->quoteIdentifier($tableName);
        $quotedColumn = $this->quoteIdentifier($columnName);
        $base = strtolower($type['base']);
        $unsigned = (bool) $type['unsigned'];

        if (in_array($base, ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'], true)) {
            [$min, $max] = $this->integerRange($base, $unsigned);
            return !$this->queryHasRows("SELECT 1 FROM {$quotedTable} WHERE {$quotedColumn} IS NOT NULL AND ({$quotedColumn} < {$min} OR {$quotedColumn} > {$max}) LIMIT 1");
        }

        if (in_array($base, ['decimal', 'numeric'], true) && count($type['args']) >= 2) {
            $precision = (int) $type['args'][0];
            $scale = (int) $type['args'][1];
            $integerDigits = max(0, $precision - $scale);
            $limit = $integerDigits > 0 ? str_repeat('9', $integerDigits) : '0';
            $fraction = $scale > 0 ? '.' . str_repeat('9', $scale) : '';
            $max = $limit . $fraction;
            $min = $unsigned ? '0' : '-' . $max;
            return !$this->queryHasRows("SELECT 1 FROM {$quotedTable} WHERE {$quotedColumn} IS NOT NULL AND ({$quotedColumn} < {$min} OR {$quotedColumn} > {$max} OR {$quotedColumn} <> ROUND({$quotedColumn}, {$scale})) LIMIT 1");
        }

        if (in_array($base, ['varchar', 'char', 'binary', 'varbinary'], true) && isset($type['args'][0])) {
            $length = (int) $type['args'][0];
            return !$this->queryHasRows("SELECT 1 FROM {$quotedTable} WHERE {$quotedColumn} IS NOT NULL AND CHAR_LENGTH({$quotedColumn}) > {$length} LIMIT 1");
        }

        if ($base === 'enum' && $type['enum_values'] !== []) {
            $allowed = implode(', ', array_map(fn (string $value): string => $this->quoteValue($value), $type['enum_values']));
            return !$this->queryHasRows("SELECT 1 FROM {$quotedTable} WHERE {$quotedColumn} IS NOT NULL AND {$quotedColumn} NOT IN ({$allowed}) LIMIT 1");
        }

        $current = $this->columnInfo($tableName, $columnName);
        if (!$current) {
            return false;
        }

        $currentBase = strtolower((string) $current->data_type);
        $compatibleFamilies = [
            ['varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext', 'enum'],
            ['date', 'datetime', 'timestamp'],
            ['time'],
            ['float', 'double', 'real'],
            ['blob', 'tinyblob', 'mediumblob', 'longblob', 'binary', 'varbinary'],
        ];
        foreach ($compatibleFamilies as $family) {
            if (in_array($base, $family, true) && in_array($currentBase, $family, true)) {
                return true;
            }
        }

        return $base === $currentBase;
    }

    private function extractType(string $definition): ?array
    {
        if (!preg_match('/^\\s*([A-Za-z]+)(?:\\s*\\((.*?)\\))?(?:\\s+(UNSIGNED))?/i', $definition, $matches)) {
            return null;
        }

        $base = strtolower($matches[1]);
        $rawArgs = $matches[2] ?? '';
        $args = [];
        $enumValues = [];
        if ($rawArgs !== '') {
            if ($base === 'enum') {
                preg_match_all("/'((?:\\\\.|[^'])*)'/", $rawArgs, $values);
                $enumValues = array_map(static fn (string $value): string => stripcslashes($value), $values[1] ?? []);
            } else {
                $args = array_map('trim', explode(',', $rawArgs));
            }
        }

        return [
            'base' => $base,
            'args' => $args,
            'unsigned' => isset($matches[3]) && $matches[3] !== '',
            'enum_values' => $enumValues,
        ];
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

        return [(string) (-(2 ** ($bits - 1))), (string) ((2 ** ($bits - 1)) - 1)];
    }

    private function queryHasRows(string $sql): bool
    {
        return DB::selectOne($sql) !== null;
    }

    private function indexDefinition(string $tableName, string $indexName): ?array
    {
        if ($this->isSqlite()) {
            return null;
        }

        $rows = DB::table('information_schema.STATISTICS')
            ->select(['COLUMN_NAME', 'NON_UNIQUE'])
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('INDEX_NAME', $indexName)
            ->orderBy('SEQ_IN_INDEX')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'columns' => $rows->pluck('COLUMN_NAME')->map(fn ($value): string => (string) $value)->all(),
            'unique' => (int) $rows->first()->NON_UNIQUE === 0,
        ];
    }

    private function equivalentIndexExists(string $tableName, array $columns, bool $unique): bool
    {
        $names = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->distinct()
            ->pluck('INDEX_NAME');

        foreach ($names as $name) {
            $definition = $this->indexDefinition($tableName, (string) $name);
            if ($definition !== null && $definition['columns'] === $columns && $definition['unique'] === $unique) {
                return true;
            }
        }
        return false;
    }

    private function primaryKeyColumns(string $tableName): array
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('CONSTRAINT_NAME', 'PRIMARY')
            ->orderBy('ORDINAL_POSITION')
            ->pluck('COLUMN_NAME')
            ->map(fn ($value): string => (string) $value)
            ->all();
    }

    private function foreignDefinition(string $tableName, string $foreignName): ?array
    {
        $rows = DB::table('information_schema.KEY_COLUMN_USAGE as k')
            ->join('information_schema.REFERENTIAL_CONSTRAINTS as r', function ($join): void {
                $join->on('r.CONSTRAINT_SCHEMA', '=', 'k.CONSTRAINT_SCHEMA')
                    ->on('r.TABLE_NAME', '=', 'k.TABLE_NAME')
                    ->on('r.CONSTRAINT_NAME', '=', 'k.CONSTRAINT_NAME');
            })
            ->select(['k.COLUMN_NAME', 'k.REFERENCED_TABLE_NAME', 'k.REFERENCED_COLUMN_NAME', 'r.DELETE_RULE', 'r.UPDATE_RULE'])
            ->where('k.CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('k.TABLE_NAME', $tableName)
            ->where('k.CONSTRAINT_NAME', $foreignName)
            ->orderBy('k.ORDINAL_POSITION')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'columns' => $rows->pluck('COLUMN_NAME')->map(fn ($value): string => (string) $value)->all(),
            'reference_table' => (string) $rows->first()->REFERENCED_TABLE_NAME,
            'reference_columns' => $rows->pluck('REFERENCED_COLUMN_NAME')->map(fn ($value): string => (string) $value)->all(),
            'on_delete' => $this->normalizeRule((string) $rows->first()->DELETE_RULE),
            'on_update' => $this->normalizeRule((string) $rows->first()->UPDATE_RULE),
        ];
    }

    private function equivalentForeignExists(
        string $tableName,
        array $columns,
        string $referenceTable,
        array $referenceColumns,
        ?string $onDelete,
        ?string $onUpdate
    ): bool {
        $names = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->pluck('CONSTRAINT_NAME');

        $expected = [
            'columns' => $columns,
            'reference_table' => $referenceTable,
            'reference_columns' => $referenceColumns,
            'on_delete' => $this->normalizeRule($onDelete),
            'on_update' => $this->normalizeRule($onUpdate),
        ];
        foreach ($names as $name) {
            if ($this->foreignDefinition($tableName, (string) $name) === $expected) {
                return true;
            }
        }
        return false;
    }

    private function normalizeRule(?string $rule): string
    {
        $normalized = strtoupper(trim((string) $rule));
        return $normalized === '' ? 'NO ACTION' : $normalized;
    }

    private function hasOrphanForeignValues(string $tableName, string $columnName, string $referenceTable, string $referenceColumn): bool
    {
        $sql = sprintf(
            'SELECT 1 FROM %s local_table LEFT JOIN %s reference_table ON local_table.%s = reference_table.%s WHERE local_table.%s IS NOT NULL AND reference_table.%s IS NULL LIMIT 1',
            $this->quoteIdentifier($tableName),
            $this->quoteIdentifier($referenceTable),
            $this->quoteIdentifier($columnName),
            $this->quoteIdentifier($referenceColumn),
            $this->quoteIdentifier($columnName),
            $this->quoteIdentifier($referenceColumn)
        );
        return $this->queryHasRows($sql);
    }

    private function hasDuplicateValues(string $tableName, array $columns, bool $ignoreNulls = true): bool
    {
        $quoted = array_map(fn (string $column): string => $this->quoteIdentifier($column), $columns);
        $where = '';
        if ($ignoreNulls) {
            $where = ' WHERE ' . implode(' AND ', array_map(fn (string $column): string => $column . ' IS NOT NULL', $quoted));
        }
        $sql = sprintf(
            'SELECT 1 FROM %s%s GROUP BY %s HAVING COUNT(*) > 1 LIMIT 1',
            $this->quoteIdentifier($tableName),
            $where,
            implode(', ', $quoted)
        );
        return $this->queryHasRows($sql);
    }

    private function hasNullValues(string $tableName, array $columns): bool
    {
        $conditions = array_map(fn (string $column): string => $this->quoteIdentifier($column) . ' IS NULL', $columns);
        $sql = sprintf(
            'SELECT 1 FROM %s WHERE %s LIMIT 1',
            $this->quoteIdentifier($tableName),
            implode(' OR ', $conditions)
        );
        return $this->queryHasRows($sql);
    }

    private function allColumnsExist(string $tableName, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($tableName, $column)) {
                return false;
            }
        }
        return true;
    }

    private function columnInfo(string $tableName, string $columnName): ?object
    {
        return DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->select(['COLUMN_TYPE as column_type', 'DATA_TYPE as data_type', 'IS_NULLABLE as is_nullable', 'COLUMN_DEFAULT as column_default', 'COLUMN_COMMENT as column_comment', 'EXTRA as extra'])
            ->first();
    }

    private function renderCurrentColumnDefinition(object $column, bool $autoIncrement): string
    {
        $nullable = strtoupper((string) $column->is_nullable) === 'YES';
        return strtoupper((string) $column->column_type)
            . ($nullable ? ' NULL' : ' NOT NULL')
            . $this->renderDefaultClause($column, $nullable)
            . $this->renderCommentClause((string) $column->column_comment)
            . ($autoIncrement ? ' AUTO_INCREMENT' : '');
    }

    private function renderDefaultClause(object $column, bool $nullable): string
    {
        $default = $column->column_default;
        if ($default === null) {
            return $nullable ? ' DEFAULT NULL' : '';
        }

        $value = (string) $default;
        if (preg_match('/^(CURRENT_TIMESTAMP(?:\\(\\d+\\))?|NULL)$/i', $value)) {
            return ' DEFAULT ' . strtoupper($value);
        }
        return ' DEFAULT ' . $this->quoteValue($value);
    }

    private function renderCommentClause(string $comment): string
    {
        return $comment === '' ? '' : ' COMMENT ' . $this->quoteValue($comment);
    }

    private function columnStartsAnyIndex(string $tableName, string $columnName): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->where('SEQ_IN_INDEX', 1)
            ->exists();
    }

    private function normalizeType(string $type): string
    {
        return strtolower(preg_replace('/\\s+/', ' ', trim($type)) ?? trim($type));
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

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }


    private function columnParticipatesInForeignKey(string $tableName, string $columnName): bool
    {
        if ($this->isSqlite() || !Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $columnName)) {
            return false;
        }

        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }

    private function wouldRemoveUnsignedInteger(string $currentType, string $requestedDefinition): bool
    {
        $currentType = strtolower(trim($currentType));

        if (!str_contains($currentType, 'unsigned')) {
            return false;
        }

        if (!preg_match('/^\s*(tinyint|smallint|mediumint|int|integer|bigint)\b/i', $requestedDefinition)) {
            return false;
        }

        return !preg_match('/\bunsigned\b/i', $requestedDefinition);
    }


};
