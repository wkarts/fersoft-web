<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pedido_mesas')) {
            return;
        }

        $this->addForeignIfMissing('pedido_mesas', 'pedido_mesas_filial_id_foreign', 'filial_id', 'filials', 'id', 'cascade', null);
    }

    public function down()
    {
        // Migration exclusivamente evolutiva: não executar downgrade, DROP ou remoção.
        return;
    }

    protected function ensureColumn(string $tableName, string $columnName, \Closure $definition): void
    {
        if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($definition): void {
            $definition($table);
        });
    }

    protected function ensureBaseTenantColumns(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        // Em tabela já populada, empresa_id nasce nullable para não invalidar registros legados.
        $this->ensureColumn($tableName, 'empresa_id', static function (Blueprint $table): void {
            $table->unsignedInteger('empresa_id')->nullable();
        });
        $this->ensureColumn($tableName, 'filial_id', static function (Blueprint $table): void {
            $table->unsignedInteger('filial_id')->nullable();
        });
        $this->ensureColumn($tableName, 'usuario_id', static function (Blueprint $table): void {
            $table->unsignedInteger('usuario_id')->nullable();
        });
    }

    protected function addIndexIfMissing(
        string $tableName,
        string $indexName,
        array $columns,
        bool $unique = false
    ): void {
        if (
            !Schema::hasTable($tableName)
            || !$this->allColumnsExist($tableName, $columns)
            || $this->indexExists($tableName, $indexName)
            || $this->equivalentIndexExists($tableName, $columns, $unique)
        ) {
            return;
        }

        if ($unique && $this->hasDuplicateValues($tableName, $columns)) {
            $this->warnForwardOnly("Índice único {$indexName} ignorado: existem duplicidades em {$tableName}.");
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName, $unique): void {
            if ($unique) {
                $table->unique($columns, $indexName);
                return;
            }

            $table->index($columns, $indexName);
        });
    }

    protected function addPrimaryKeyIfMissing(string $tableName, array $columns): void
    {
        if (
            $this->isSqlite()
            || !Schema::hasTable($tableName)
            || !$this->allColumnsExist($tableName, $columns)
            || $this->primaryKeyExists($tableName)
        ) {
            return;
        }

        if ($this->hasNullValues($tableName, $columns) || $this->hasDuplicateValues($tableName, $columns, false)) {
            $this->warnForwardOnly("Chave primária ignorada em {$tableName}: há valores nulos ou duplicados.");
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns): void {
            $table->primary($columns);
        });
    }

    protected function ensureAutoIncrement(string $tableName, string $columnName): void
    {
        if (!$this->isMysql() || !Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        $column = $this->columnInfo($tableName, $columnName);
        if (!$column || str_contains(strtolower((string) $column->extra), 'auto_increment')) {
            return;
        }

        if (!$this->isIntegerType((string) $column->column_type) || !$this->columnStartsAnyIndex($tableName, $columnName)) {
            $this->warnForwardOnly("AUTO_INCREMENT ignorado em {$tableName}.{$columnName}: a coluna não é inteira/indexada.");
            return;
        }

        $definition = $this->renderColumnDefinition(
            $column,
            (string) $column->column_type,
            strtoupper((string) $column->is_nullable) === 'YES',
            $this->currentDefaultSql($column),
            (string) $column->column_comment,
            true,
            $this->extractOnUpdate((string) $column->extra)
        );

        DB::statement(sprintf(
            'ALTER TABLE %s MODIFY COLUMN %s %s',
            $this->quoteIdentifier($tableName),
            $this->quoteIdentifier($columnName),
            $definition
        ));
    }

    protected function addForeignIfMissing(
        string $tableName,
        string $foreignName,
        string $column,
        string $referenceTable,
        string $referenceColumn = 'id',
        ?string $onDelete = null,
        ?string $onUpdate = null
    ): void {
        if (
            $this->isSqlite()
            || !Schema::hasTable($tableName)
            || !Schema::hasTable($referenceTable)
            || !Schema::hasColumn($tableName, $column)
            || !Schema::hasColumn($referenceTable, $referenceColumn)
            || $this->foreignExists($tableName, $foreignName)
            || $this->equivalentForeignExists($tableName, $column, $referenceTable, $referenceColumn)
        ) {
            return;
        }

        if (!$this->normalizeForeignColumnForwardOnly($tableName, $column, $referenceTable, $referenceColumn)) {
            $this->warnForwardOnly("FK {$foreignName} ignorada: tipos incompatíveis exigiriam redução/conversão insegura.");
            return;
        }

        if (strtolower((string) $onDelete) === 'set null') {
            $this->makeColumnNullableForwardOnly($tableName, $column);
        }

        if ($this->hasOrphanForeignValues($tableName, $column, $referenceTable, $referenceColumn)) {
            $this->warnForwardOnly("FK {$foreignName} ignorada: existem registros órfãos em {$tableName}.{$column}.");
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use (
            $foreignName,
            $column,
            $referenceTable,
            $referenceColumn,
            $onDelete,
            $onUpdate
        ): void {
            $foreign = $table->foreign($column, $foreignName)
                ->references($referenceColumn)
                ->on($referenceTable);

            if ($onDelete !== null) {
                $foreign->onDelete($onDelete);
            }
            if ($onUpdate !== null) {
                $foreign->onUpdate($onUpdate);
            }
        });
    }

    protected function applySafeColumnUpgrade(array $upgrade): void
    {
        if (!$this->isMysql()) {
            return;
        }

        $tableName = (string) $upgrade['table'];
        $columnName = (string) $upgrade['column'];

        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        $current = $this->columnInfo($tableName, $columnName);
        if (!$current) {
            return;
        }

        $currentType = (string) $current->column_type;
        $desiredType = (string) $upgrade['type'];
        $targetType = $currentType;
        $typeChanged = false;

        if ($this->isSafeTypeUpgrade($currentType, $desiredType)) {
            $targetType = $desiredType;
            $typeChanged = $this->normalizeType($currentType) !== $this->normalizeType($desiredType);
        }

        $currentNullable = strtoupper((string) $current->is_nullable) === 'YES';
        $desiredNullable = (bool) $upgrade['nullable'];
        $targetNullable = $desiredNullable ? true : $currentNullable;
        $nullableChanged = $targetNullable !== $currentNullable;

        $currentDefaultSql = $this->currentDefaultSql($current);
        $targetDefaultSql = $currentDefaultSql;
        $defaultChanged = false;

        if ((bool) $upgrade['default_specified']) {
            $candidateDefault = $upgrade['default_sql'];
            if ($candidateDefault !== null && strtoupper(trim((string) $candidateDefault)) === 'NULL' && !$targetNullable) {
                $candidateDefault = $currentDefaultSql;
            }
            $targetDefaultSql = $candidateDefault;
            $defaultChanged = !$this->defaultSqlEquivalent($currentDefaultSql, $targetDefaultSql);
        }

        $currentComment = (string) $current->column_comment;
        $desiredComment = $upgrade['comment'];
        $targetComment = $desiredComment !== null ? (string) $desiredComment : $currentComment;
        $commentChanged = $targetComment !== $currentComment;

        $currentExtra = strtolower((string) $current->extra);
        $targetAutoIncrement = str_contains($currentExtra, 'auto_increment');
        $autoIncrementChanged = false;
        if ((bool) $upgrade['auto_increment'] && !$targetAutoIncrement) {
            if ($this->isIntegerType($targetType) && $this->columnStartsAnyIndex($tableName, $columnName)) {
                $targetAutoIncrement = true;
                $autoIncrementChanged = true;
            }
        }

        $currentOnUpdate = $this->extractOnUpdate((string) $current->extra);
        $targetOnUpdate = $upgrade['on_update'] !== null
            ? (string) $upgrade['on_update']
            : $currentOnUpdate;
        $onUpdateChanged = $this->normalizeExpression($currentOnUpdate) !== $this->normalizeExpression($targetOnUpdate);

        if (!$typeChanged && !$nullableChanged && !$defaultChanged && !$commentChanged && !$autoIncrementChanged && !$onUpdateChanged) {
            return;
        }

        $definition = $this->renderColumnDefinition(
            $current,
            $targetType,
            $targetNullable,
            $targetDefaultSql,
            $targetComment,
            $targetAutoIncrement,
            $targetOnUpdate
        );

        DB::statement(sprintf(
            'ALTER TABLE %s MODIFY COLUMN %s %s',
            $this->quoteIdentifier($tableName),
            $this->quoteIdentifier($columnName),
            $definition
        ));
    }

    private function makeColumnNullableForwardOnly(string $tableName, string $columnName): void
    {
        if (!$this->isMysql()) {
            return;
        }

        $column = $this->columnInfo($tableName, $columnName);
        if (!$column || strtoupper((string) $column->is_nullable) === 'YES') {
            return;
        }

        $definition = $this->renderColumnDefinition(
            $column,
            (string) $column->column_type,
            true,
            $this->currentDefaultSql($column) ?? 'NULL',
            (string) $column->column_comment,
            str_contains(strtolower((string) $column->extra), 'auto_increment'),
            $this->extractOnUpdate((string) $column->extra)
        );

        DB::statement(sprintf(
            'ALTER TABLE %s MODIFY COLUMN %s %s',
            $this->quoteIdentifier($tableName),
            $this->quoteIdentifier($columnName),
            $definition
        ));
    }

    private function normalizeForeignColumnForwardOnly(
        string $tableName,
        string $column,
        string $referenceTable,
        string $referenceColumn
    ): bool {
        if (!$this->isMysql()) {
            return true;
        }

        $local = $this->columnInfo($tableName, $column);
        $referenced = $this->columnInfo($referenceTable, $referenceColumn);
        if (!$local || !$referenced) {
            return false;
        }

        $localType = (string) $local->column_type;
        $referencedType = (string) $referenced->column_type;

        if ($this->normalizeType($localType) === $this->normalizeType($referencedType)) {
            return true;
        }

        if (!$this->isSafeTypeUpgrade($localType, $referencedType)) {
            return false;
        }

        $definition = $this->renderColumnDefinition(
            $local,
            $referencedType,
            strtoupper((string) $local->is_nullable) === 'YES',
            $this->currentDefaultSql($local),
            (string) $local->column_comment,
            str_contains(strtolower((string) $local->extra), 'auto_increment'),
            $this->extractOnUpdate((string) $local->extra)
        );

        DB::statement(sprintf(
            'ALTER TABLE %s MODIFY COLUMN %s %s',
            $this->quoteIdentifier($tableName),
            $this->quoteIdentifier($column),
            $definition
        ));

        return true;
    }

    private function renderColumnDefinition(
        object $current,
        string $type,
        bool $nullable,
        ?string $defaultSql,
        string $comment,
        bool $autoIncrement,
        ?string $onUpdate
    ): string {
        $parts = [$type];
        $dataType = strtolower((string) $current->data_type);

        if ($this->isCharacterType($dataType)) {
            if (!empty($current->character_set_name)) {
                $parts[] = 'CHARACTER SET ' . $this->quoteIdentifier((string) $current->character_set_name);
            }
            if (!empty($current->collation_name)) {
                $parts[] = 'COLLATE ' . $this->quoteIdentifier((string) $current->collation_name);
            }
        }

        $parts[] = $nullable ? 'NULL' : 'NOT NULL';

        if ($defaultSql !== null) {
            $parts[] = 'DEFAULT ' . $defaultSql;
        }

        if ($autoIncrement) {
            $parts[] = 'AUTO_INCREMENT';
        }

        if ($onUpdate !== null) {
            $parts[] = 'ON UPDATE ' . $onUpdate;
        }

        if ($comment !== '') {
            $parts[] = 'COMMENT ' . DB::connection()->getPdo()->quote($comment);
        }

        return implode(' ', $parts);
    }

    private function currentDefaultSql(object $column): ?string
    {
        $value = $column->column_default;
        $nullable = strtoupper((string) $column->is_nullable) === 'YES';

        if ($value === null) {
            return $nullable ? 'NULL' : null;
        }

        $valueString = (string) $value;
        if ($this->isSqlExpressionDefault($valueString)) {
            return strtoupper($valueString);
        }

        if ($this->isNumericType((string) $column->data_type) && is_numeric($valueString)) {
            return $valueString;
        }

        return DB::connection()->getPdo()->quote($valueString);
    }

    private function defaultSqlEquivalent(?string $left, ?string $right): bool
    {
        if ($left === null || $right === null) {
            return $left === $right;
        }

        $normalize = static function (string $value): string {
            $value = trim($value);
            $upper = strtoupper($value);
            if ($upper === 'CURRENT_TIMESTAMP()') {
                return 'CURRENT_TIMESTAMP';
            }
            if ($upper === 'NULL') {
                return 'NULL';
            }
            if ((str_starts_with($value, "'") && str_ends_with($value, "'"))
                || (str_starts_with($value, '"') && str_ends_with($value, '"'))) {
                return substr($value, 1, -1);
            }
            if (is_numeric($value)) {
                return rtrim(rtrim(number_format((float) $value, 12, '.', ''), '0'), '.');
            }
            return $upper;
        };

        return $normalize($left) === $normalize($right);
    }

    private function isSafeTypeUpgrade(string $currentType, string $desiredType): bool
    {
        $current = $this->normalizeType($currentType);
        $desired = $this->normalizeType($desiredType);
        if ($current === $desired) {
            return true;
        }

        $currentInteger = $this->parseIntegerType($current);
        $desiredInteger = $this->parseIntegerType($desired);
        if ($currentInteger && $desiredInteger) {
            return $currentInteger['unsigned'] === $desiredInteger['unsigned']
                && $desiredInteger['rank'] >= $currentInteger['rank'];
        }

        $currentVarchar = $this->parseLengthType($current, ['varchar', 'char', 'varbinary', 'binary']);
        $desiredVarchar = $this->parseLengthType($desired, ['varchar', 'char', 'varbinary', 'binary']);
        if ($currentVarchar && $desiredVarchar && $currentVarchar['name'] === $desiredVarchar['name']) {
            return $desiredVarchar['length'] >= $currentVarchar['length'];
        }

        $currentDecimal = $this->parseDecimalType($current);
        $desiredDecimal = $this->parseDecimalType($desired);
        if ($currentDecimal && $desiredDecimal) {
            $currentIntegerDigits = $currentDecimal['precision'] - $currentDecimal['scale'];
            $desiredIntegerDigits = $desiredDecimal['precision'] - $desiredDecimal['scale'];
            return $currentDecimal['unsigned'] === $desiredDecimal['unsigned']
                && $desiredIntegerDigits >= $currentIntegerDigits
                && $desiredDecimal['scale'] >= $currentDecimal['scale'];
        }

        $textRank = ['tinytext' => 1, 'text' => 2, 'mediumtext' => 3, 'longtext' => 4];
        if (isset($textRank[$current], $textRank[$desired])) {
            return $textRank[$desired] >= $textRank[$current];
        }

        $blobRank = ['tinyblob' => 1, 'blob' => 2, 'mediumblob' => 3, 'longblob' => 4];
        if (isset($blobRank[$current], $blobRank[$desired])) {
            return $blobRank[$desired] >= $blobRank[$current];
        }

        if ($current === 'float' && $desired === 'double') {
            return true;
        }

        $currentEnum = $this->parseEnumValues($current);
        $desiredEnum = $this->parseEnumValues($desired);
        if ($currentEnum !== null && $desiredEnum !== null) {
            return array_diff($currentEnum, $desiredEnum) === [];
        }

        if ($current === 'date' && $desired === 'datetime') {
            return true;
        }

        return false;
    }

    private function parseIntegerType(string $type): ?array
    {
        if (!preg_match('/^(tinyint|smallint|mediumint|int|integer|bigint)(?:\(\d+\))?( unsigned)?$/', $type, $match)) {
            return null;
        }

        $rank = [
            'tinyint' => 1,
            'smallint' => 2,
            'mediumint' => 3,
            'int' => 4,
            'integer' => 4,
            'bigint' => 5,
        ];

        return [
            'rank' => $rank[$match[1]],
            'unsigned' => isset($match[2]) && trim($match[2]) === 'unsigned',
        ];
    }

    private function parseLengthType(string $type, array $allowed): ?array
    {
        if (!preg_match('/^([a-z]+)\((\d+)\)$/', $type, $match) || !in_array($match[1], $allowed, true)) {
            return null;
        }

        return ['name' => $match[1], 'length' => (int) $match[2]];
    }

    private function parseDecimalType(string $type): ?array
    {
        if (!preg_match('/^(decimal|numeric)\((\d+),(\d+)\)( unsigned)?$/', $type, $match)) {
            return null;
        }

        return [
            'precision' => (int) $match[2],
            'scale' => (int) $match[3],
            'unsigned' => isset($match[4]) && trim($match[4]) === 'unsigned',
        ];
    }

    private function parseEnumValues(string $type): ?array
    {
        if (!preg_match('/^enum\((.*)\)$/', $type, $match)) {
            return null;
        }

        return str_getcsv($match[1], ',', "'", '\\');
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        $type = preg_replace('/\s+/', ' ', $type) ?? $type;
        $type = preg_replace('/\s*,\s*/', ',', $type) ?? $type;
        $type = preg_replace('/^(smallint|mediumint|int|integer|bigint)\(\d+\)/', '$1', $type) ?? $type;
        return $type;
    }

    private function extractOnUpdate(string $extra): ?string
    {
        if (preg_match('/on update\s+([^ ]+(?:\(\))?)/i', $extra, $match)) {
            return strtoupper($match[1]);
        }

        return null;
    }

    private function normalizeExpression(?string $expression): ?string
    {
        if ($expression === null) {
            return null;
        }

        $value = strtoupper(trim($expression));
        return $value === 'CURRENT_TIMESTAMP()' ? 'CURRENT_TIMESTAMP' : $value;
    }

    private function isSqlExpressionDefault(string $value): bool
    {
        return (bool) preg_match('/^(CURRENT_TIMESTAMP(?:\(\))?|CURRENT_DATE(?:\(\))?|CURRENT_TIME(?:\(\))?|NULL)$/i', trim($value));
    }

    private function isIntegerType(string $type): bool
    {
        return $this->parseIntegerType($this->normalizeType($type)) !== null;
    }

    private function isNumericType(string $dataType): bool
    {
        return in_array(strtolower($dataType), [
            'tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint',
            'decimal', 'numeric', 'float', 'double', 'real', 'bit',
        ], true);
    }

    private function isCharacterType(string $dataType): bool
    {
        return in_array(strtolower($dataType), [
            'char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'enum', 'set',
        ], true);
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
        if (!$this->isMysql()) {
            return null;
        }

        return DB::selectOne(
            'SELECT COLUMN_TYPE AS column_type, DATA_TYPE AS data_type, IS_NULLABLE AS is_nullable, '
            . 'COLUMN_DEFAULT AS column_default, EXTRA AS extra, COLUMN_COMMENT AS column_comment, '
            . 'CHARACTER_SET_NAME AS character_set_name, COLLATION_NAME AS collation_name '
            . 'FROM information_schema.COLUMNS '
            . 'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), $tableName, $columnName]
        );
    }

    private function indexRows(string $tableName): array
    {
        if (!$this->isMysql() || !Schema::hasTable($tableName)) {
            return [];
        }

        return DB::select(
            'SELECT INDEX_NAME AS index_name, NON_UNIQUE AS non_unique, SEQ_IN_INDEX AS seq_in_index, '
            . 'COLUMN_NAME AS column_name FROM information_schema.STATISTICS '
            . 'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY INDEX_NAME, SEQ_IN_INDEX',
            [DB::getDatabaseName(), $tableName]
        );
    }

    private function groupedIndexes(string $tableName): array
    {
        $indexes = [];
        foreach ($this->indexRows($tableName) as $row) {
            $name = (string) $row->index_name;
            $indexes[$name] ??= ['unique' => (int) $row->non_unique === 0, 'columns' => []];
            $indexes[$name]['columns'][] = (string) $row->column_name;
        }
        return $indexes;
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        if (!Schema::hasTable($tableName)) {
            return false;
        }

        if ($this->isSqlite()) {
            $safeTableName = str_replace("'", "''", $tableName);
            $indexes = DB::select("PRAGMA index_list('" . $safeTableName . "')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        return array_key_exists($indexName, $this->groupedIndexes($tableName));
    }

    private function equivalentIndexExists(string $tableName, array $columns, bool $unique): bool
    {
        foreach ($this->groupedIndexes($tableName) as $index) {
            if ($index['columns'] === array_values($columns) && (!$unique || $index['unique'])) {
                return true;
            }
        }
        return false;
    }

    private function primaryKeyExists(string $tableName): bool
    {
        return $this->indexExists($tableName, 'PRIMARY');
    }

    private function columnStartsAnyIndex(string $tableName, string $columnName): bool
    {
        foreach ($this->groupedIndexes($tableName) as $index) {
            if (($index['columns'][0] ?? null) === $columnName) {
                return true;
            }
        }
        return false;
    }

    private function foreignExists(string $tableName, string $foreignName): bool
    {
        if (!$this->isMysql() || !Schema::hasTable($tableName)) {
            return false;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('CONSTRAINT_NAME', $foreignName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function equivalentForeignExists(
        string $tableName,
        string $column,
        string $referenceTable,
        string $referenceColumn
    ): bool {
        if (!$this->isMysql()) {
            return false;
        }

        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $column)
            ->where('REFERENCED_TABLE_NAME', $referenceTable)
            ->where('REFERENCED_COLUMN_NAME', $referenceColumn)
            ->exists();
    }

    private function hasOrphanForeignValues(
        string $tableName,
        string $column,
        string $referenceTable,
        string $referenceColumn
    ): bool {
        $sql = sprintf(
            'SELECT 1 FROM %s AS local_row LEFT JOIN %s AS referenced_row ON local_row.%s = referenced_row.%s '
            . 'WHERE local_row.%s IS NOT NULL AND referenced_row.%s IS NULL LIMIT 1',
            $this->quoteIdentifier($tableName),
            $this->quoteIdentifier($referenceTable),
            $this->quoteIdentifier($column),
            $this->quoteIdentifier($referenceColumn),
            $this->quoteIdentifier($column),
            $this->quoteIdentifier($referenceColumn)
        );

        return DB::selectOne($sql) !== null;
    }

    private function hasNullValues(string $tableName, array $columns): bool
    {
        $conditions = array_map(
            fn (string $column): string => $this->quoteIdentifier($column) . ' IS NULL',
            $columns
        );

        $sql = sprintf(
            'SELECT 1 FROM %s WHERE %s LIMIT 1',
            $this->quoteIdentifier($tableName),
            implode(' OR ', $conditions)
        );

        return DB::selectOne($sql) !== null;
    }

    private function hasDuplicateValues(string $tableName, array $columns, bool $ignoreNulls = true): bool
    {
        $quotedColumns = array_map(fn (string $column): string => $this->quoteIdentifier($column), $columns);
        $where = '';

        if ($ignoreNulls) {
            $where = ' WHERE ' . implode(
                ' AND ',
                array_map(fn (string $column): string => $this->quoteIdentifier($column) . ' IS NOT NULL', $columns)
            );
        }

        $sql = sprintf(
            'SELECT 1 FROM %s%s GROUP BY %s HAVING COUNT(*) > 1 LIMIT 1',
            $this->quoteIdentifier($tableName),
            $where,
            implode(', ', $quotedColumns)
        );

        return DB::selectOne($sql) !== null;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function isMysql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    private function warnForwardOnly(string $message): void
    {
        if (function_exists('logger')) {
            try {
                logger()->warning('[migration-forward-only] ' . $message);
                return;
            } catch (\Throwable) {
                // Não interromper migration apenas por indisponibilidade do logger.
            }
        }
    }
};
