<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REFERENCE_COLUMNS = ['empresa_id' => ['empresas', 'id', 'cascade', 'restrict'], 'filial_id' => ['filials', 'id', 'cascade', 'restrict'], 'fornecedor_id' => ['fornecedors', 'id', 'cascade', 'restrict'], 'transportadora_id' => ['transportadoras', 'id', 'cascade', 'restrict'], 'usuario_id' => ['usuarios', 'id', 'cascade', 'restrict'], 'veiculo_id' => ['veiculos', 'id', 'set null', 'restrict']];
    private const MIGRATION_NAME = '2026_09_18_120057_update_compras_table';
    private const TABLE_NAME = 'compras';

    public function up()
    {
        $this->runWithSchemaLock(function () {
            // Valida o plano desta tabela antes do primeiro DDL; não preenche dados por suposição.
            $this->ensureTableExists('compras');
            $this->preflightColumn('compras', 'vbc_icms', ['column_type' => 'decimal(15,2)', 'nullable' => true, 'column_default' => '0.00', 'extra' => '', 'character_set_name' => null, 'collation_name' => null, 'column_comment' => null]);
            $this->preflightColumn('compras', 'v_icms', ['column_type' => 'decimal(15,2)', 'nullable' => true, 'column_default' => '0.00', 'extra' => '', 'character_set_name' => null, 'collation_name' => null, 'column_comment' => null]);
            $this->preflightColumn('compras', 'v_ipi', ['column_type' => 'decimal(15,2)', 'nullable' => true, 'column_default' => '0.00', 'extra' => '', 'character_set_name' => null, 'collation_name' => null, 'column_comment' => null]);
            $this->preflightColumn('compras', 'v_pis', ['column_type' => 'decimal(15,2)', 'nullable' => true, 'column_default' => '0.00', 'extra' => '', 'character_set_name' => null, 'collation_name' => null, 'column_comment' => null]);
            $this->preflightColumn('compras', 'v_cofins', ['column_type' => 'decimal(15,2)', 'nullable' => true, 'column_default' => '0.00', 'extra' => '', 'character_set_name' => null, 'collation_name' => null, 'column_comment' => null]);

            $this->ensureColumn('compras', 'vbc_icms', function (Blueprint $table) {
                return $table->decimal('vbc_icms', 15, 2)->nullable()->default('0.00');
            }, ['column_type' => 'decimal(15,2)', 'nullable' => true, 'column_default' => '0.00', 'extra' => '', 'character_set_name' => null, 'collation_name' => null, 'column_comment' => null]);
            $this->ensureColumn('compras', 'v_icms', function (Blueprint $table) {
                return $table->decimal('v_icms', 15, 2)->nullable()->default('0.00');
            }, ['column_type' => 'decimal(15,2)', 'nullable' => true, 'column_default' => '0.00', 'extra' => '', 'character_set_name' => null, 'collation_name' => null, 'column_comment' => null]);
            $this->ensureColumn('compras', 'v_ipi', function (Blueprint $table) {
                return $table->decimal('v_ipi', 15, 2)->nullable()->default('0.00');
            }, ['column_type' => 'decimal(15,2)', 'nullable' => true, 'column_default' => '0.00', 'extra' => '', 'character_set_name' => null, 'collation_name' => null, 'column_comment' => null]);
            $this->ensureColumn('compras', 'v_pis', function (Blueprint $table) {
                return $table->decimal('v_pis', 15, 2)->nullable()->default('0.00');
            }, ['column_type' => 'decimal(15,2)', 'nullable' => true, 'column_default' => '0.00', 'extra' => '', 'character_set_name' => null, 'collation_name' => null, 'column_comment' => null]);
            $this->ensureColumn('compras', 'v_cofins', function (Blueprint $table) {
                return $table->decimal('v_cofins', 15, 2)->nullable()->default('0.00');
            }, ['column_type' => 'decimal(15,2)', 'nullable' => true, 'column_default' => '0.00', 'extra' => '', 'character_set_name' => null, 'collation_name' => null, 'column_comment' => null]);
        });
    }

    public function down()
    {
        $this->runWithSchemaLock(function () {
            $this->rollbackRecordedChanges();
        });
    }

    private function isMysql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function migrationName(): string
    {
        // O nome da classe anônima depende do caminho físico e NÃO identifica o rollback.
        return self::MIGRATION_NAME;
    }

    private function requireAudit(): void
    {
        if ($this->isSqlite() || !$this->isMysql()) {
            throw new \RuntimeException('Esta conciliação exige MySQL 8. SQLite não valida as mesmas operações.');
        }
        $version = (string) DB::selectOne('SELECT VERSION() AS version')->version;
        if (stripos($version, 'mariadb') !== false || !preg_match('/^8\.0\./', $version)) {
            throw new \RuntimeException('Versão não homologada para esta conciliação: ' . $version);
        }
        if (!Schema::hasTable('migration_merge_audit') || !Schema::hasColumn('migration_merge_audit', 'metadata_json')) {
            throw new \RuntimeException('Execute primeiro a migration incremental de migration_merge_audit.');
        }
    }

    private function runWithSchemaLock(\Closure $operation): void
    {
        $this->requireAudit();
        $lock = 'fersoft:ddl:' . substr(hash('sha256', DB::getDatabaseName()), 0, 40);
        $obtained = DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock]);
        if ((int) ($obtained->acquired ?? 0) !== 1) {
            throw new \RuntimeException('Outra execução da conciliação detém o lock do banco.');
        }
        $mode = (string) DB::selectOne('SELECT @@SESSION.sql_mode AS sql_mode')->sql_mode;
        try {
            $modes = array_filter(explode(',', $mode));
            $modes[] = 'STRICT_ALL_TABLES';
            DB::statement('SET SESSION sql_mode = ?', [implode(',', array_unique($modes))]);
            $operation();
        } finally {
            try {
                DB::statement('SET SESSION sql_mode = ?', [$mode]);
            } finally {
                DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock]);
            }
        }
    }

    private function auditRow(string $type, string $name): ?array
    {
        $row = DB::table('migration_merge_audit')
            ->where('migration_name', $this->migrationName())
            ->where('object_type', $type)->where('object_name', $name)->first();
        if (!$row) {
            return null;
        }
        if ($row->metadata_json === null) {
            throw new \RuntimeException('Registro de auditoria sem metadados: ' . $type . ' ' . $name);
        }
        return json_decode((string) $row->metadata_json, true, 512, JSON_THROW_ON_ERROR);
    }

    private function writeAudit(string $type, string $name, array $metadata): void
    {
        DB::table('migration_merge_audit')->updateOrInsert(
            ['migration_name' => $this->migrationName(), 'object_type' => $type, 'object_name' => $name],
            ['metadata_json' => json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
             'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function wasCreated(string $type, string $name): bool
    {
        $row = $this->auditRow($type, $name);
        return $row !== null && $row['before'] === null && in_array($row['state'], ['applied', 'reverting'], true);
    }

    private function forgetCreated(string $type, string $name): void
    {
        DB::table('migration_merge_audit')->where('migration_name', $this->migrationName())
            ->where('object_type', $type)->where('object_name', $name)->delete();
    }

    private function sameSnapshot(mixed $left, mixed $right): bool
    {
        // MySQL JSON reorganiza chaves de objetos. A ordem dos arrays de colunas continua significativa.
        $canonical = function (mixed $value) use (&$canonical): mixed {
            if (!is_array($value)) { return $value; }
            foreach ($value as $key => $item) { $value[$key] = $canonical($item); }
            if (!array_is_list($value)) { ksort($value, SORT_STRING); }
            return $value;
        };
        return $canonical($left) === $canonical($right);
    }

    private function recordedObjectIsComplete(string $type, string $name): bool
    {
        $row = $this->auditRow($type, $name);
        if ($row === null) { return false; }
        $actual = $this->snapshotObject($type, $name);
        if ($row['state'] === 'applied' && $this->sameSnapshot($actual, $row['after'])) { return true; }
        if ($row['state'] === 'pending' && $this->sameSnapshot($actual, $row['resume_before'] ?? $row['before'])) { return false; }
        throw new \RuntimeException('Auditoria interrompida ou drift em ' . $type . ' ' . $name . '. Não é seguro presumir a propriedade.');
    }

    private function beginChange(string $type, string $name, ?array $before, array $desired): bool
    {
        $row = $this->auditRow($type, $name);
        if ($row !== null) {
            if ($row['state'] === 'applied') {
                if (!$this->sameSnapshot($this->snapshotObject($type, $name), $row['after'])) {
                    throw new \RuntimeException('Objeto alterado fora desta migration: ' . $name);
                }
                if ($this->sameSnapshot($row['desired'], $desired)) { return false; }
                $row['state'] = 'pending';
                $row['resume_before'] = $before;
                $row['desired'] = $desired;
                $this->writeAudit($type, $name, $row);
                return true;
            }
            if ($row['state'] === 'pending' && $this->sameSnapshot($before, $row['resume_before'] ?? $row['before'])) {
                return true; // DDL não ocorreu; a mesma intenção pode ser reaplicada.
            }
            throw new \RuntimeException('DDL interrompido/ambíguo em ' . $name . '. Preserve e revise a auditoria antes de continuar.');
        }
        $this->writeAudit($type, $name, ['state' => 'pending', 'before' => $before, 'desired' => $desired, 'after' => null]);
        return true;
    }

    private function markCreated(string $type, string $name): void
    {
        $row = $this->auditRow($type, $name);
        if ($row === null || $row['state'] !== 'pending') {
            throw new \RuntimeException('Intenção de alteração não registrada para ' . $name);
        }
        $row['after'] = $this->snapshotObject($type, $name);
        if ($row['after'] === null) {
            throw new \RuntimeException('Objeto não encontrado depois do DDL: ' . $name);
        }
        $row['state'] = 'applied';
        $this->writeAudit($type, $name, $row);
        // A tabela própria inclui as normalizações e FKs feitas depois de Schema::create.
        if ($type !== 'table' && $this->wasCreated('table', self::TABLE_NAME)) {
            $table = $this->auditRow('table', self::TABLE_NAME);
            $table['after'] = $this->tableSnapshot(self::TABLE_NAME);
            $this->writeAudit('table', self::TABLE_NAME, $table);
        }
    }

    private function ensureTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            throw new \RuntimeException('Tabela pré-requisito ausente: ' . $tableName . '. Não será ignorada.');
        }
    }

    private function columnInfo(string $tableName, string $columnName): ?array
    {
        $row = DB::selectOne(
            'SELECT COLUMN_NAME AS column_name, COLUMN_TYPE AS column_type, DATA_TYPE AS data_type,
             IS_NULLABLE AS is_nullable, COLUMN_DEFAULT AS column_default, EXTRA AS extra,
             COLUMN_COMMENT AS column_comment, CHARACTER_SET_NAME AS character_set_name,
             COLLATION_NAME AS collation_name, GENERATION_EXPRESSION AS generation_expression
             FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), $tableName, $columnName]
        );
        if (!$row) {
            return null;
        }
        $d = (array) $row;
        $d['nullable'] = $d['is_nullable'] === 'YES';
        unset($d['is_nullable']);
        $d['raw'] = $this->rawColumnDefinition($tableName, $d['column_name']);
        return $d;
    }

    private function rawColumnDefinition(string $tableName, string $columnName): string
    {
        $row = (array) DB::selectOne('SHOW CREATE TABLE ' . $this->quoteIdentifier($tableName));
        $sql = $row['Create Table'] ?? null;
        if (!is_string($sql)) {
            throw new \RuntimeException('SHOW CREATE TABLE inválido em ' . $tableName);
        }
        $start = strpos($sql, '(') + 1;
        $quote = null;
        $depth = 0;
        $part = '';
        $prefix = $this->quoteIdentifier($columnName) . ' ';
        for ($i = $start, $n = strlen($sql); $i < $n; $i++) {
            $ch = $sql[$i];
            if ($quote !== null) {
                $part .= $ch;
                if ($ch === '\\') {
                    if ($i + 1 < $n) { $part .= $sql[++$i]; }
                } elseif ($ch === $quote) {
                    if ($i + 1 < $n && $sql[$i + 1] === $quote) { $part .= $sql[++$i]; }
                    else { $quote = null; }
                }
                continue;
            }
            if ($ch === "'" || $ch === '"' || $ch === '`') { $quote = $ch; $part .= $ch; continue; }
            if ($ch === '(') { $depth++; }
            if ($ch === ')' && $depth > 0) { $depth--; $part .= $ch; continue; }
            if (($ch === ',' || $ch === ')') && $depth === 0) {
                $trimmed = trim($part);
                if (str_starts_with($trimmed, $prefix)) { return substr($trimmed, strlen($prefix)); }
                $part = '';
                if ($ch === ')') { break; }
            } else { $part .= $ch; }
        }
        throw new \RuntimeException('Não foi possível preservar a definição exata de ' . $tableName . '.' . $columnName);
    }

    private function normalizeType(string $type): string
    {
        $type = preg_replace('/^(?:integer)\b/i', 'int', trim($type));
        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint)(?:\(\d+\))?(\s+unsigned)?$/i', $type, $m)) {
            return strtolower($m[1] . (isset($m[2]) ? ' unsigned' : ''));
        }
        if (preg_match('/^(decimal|numeric)\s*\(\s*(\d+)\s*,\s*(\d+)\s*\)(\s+unsigned)?$/i', $type, $m)) {
            return 'decimal(' . $m[2] . ',' . $m[3] . ')' . (isset($m[4]) ? ' unsigned' : '');
        }
        // Não aplicar strtolower aos valores de ENUM/SET nem aos comentários.
        return preg_replace_callback('/^[A-Za-z]+/', static fn ($m) => strtolower($m[0]), $type);
    }

    private function normalizeDefault(mixed $value, string $type): mixed
    {
        if ($value === null) { return null; }
        $value = (string) $value;
        if (preg_match('/^CURRENT_TIMESTAMP(?:\(0?\))?$/i', $value)) { return 'CURRENT_TIMESTAMP'; }
        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|double|float)\b/i', $type)
            && preg_match('/^[+-]?\d+(?:\.\d+)?$/D', $value)) {
            $negative = str_starts_with($value, '-');
            $parts = explode('.', ltrim($value, '+-'), 2);
            $integer = ltrim($parts[0], '0');
            $integer = $integer === '' ? '0' : $integer;
            $fraction = isset($parts[1]) ? rtrim($parts[1], '0') : '';
            $number = $integer . ($fraction !== '' ? '.' . $fraction : '');
            return $negative && $number !== '0' ? '-' . $number : $number;
        }
        return $value; // 'NULL' literal não é NULL; 10 não pode ser reduzido a 1.
    }

    private function sameColumn(array $current, array $expected): bool
    {
        foreach (['column_type', 'nullable', 'column_default', 'extra', 'character_set_name', 'collation_name'] as $attribute) {
            $a = $current[$attribute] ?? null;
            $b = $expected[$attribute] ?? null;
            if ($attribute === 'column_type') { $a = $this->normalizeType($a); $b = $this->normalizeType($b); }
            if ($attribute === 'column_default') {
                $a = $this->normalizeDefault($a, $current['column_type']);
                $b = $this->normalizeDefault($b, $expected['column_type']);
            }
            if ($attribute === 'extra') {
                // DEFAULT_GENERATED é metadado de expressão; INVISIBLE deve ser preservado.
                $a = trim(str_ireplace(['DEFAULT_GENERATED', 'INVISIBLE'], '', (string) $a));
                $b = trim(str_ireplace(['DEFAULT_GENERATED', 'INVISIBLE'], '', (string) $b));
                $a = strtolower(str_replace('current_timestamp()', 'current_timestamp', $a));
                $b = strtolower(str_replace('current_timestamp()', 'current_timestamp', $b));
            }
            if ($a !== $b) { return false; }
        }
        if (isset($expected['column_comment']) && $expected['column_comment'] !== $current['column_comment']) { return false; }
        return true;
    }

    private function expectedColumnForReferences(string $columnName, array $expected): array
    {
        // Mapa local, explícito, apenas das relações desta tabela. Nunca modifica o pai.
        $reference = self::REFERENCE_COLUMNS[$columnName] ?? null;
        if ($reference === null) { return $expected; }
        $parent = $this->columnInfo($reference[0], $reference[1]);
        if ($parent === null) { throw new \RuntimeException('Referência ausente: ' . $reference[0] . '.' . $reference[1]); }
        if (!preg_match('/^(tinyint|smallint|mediumint|int|bigint)( unsigned)?$/D', $this->normalizeType($parent['column_type']))) {
            throw new \RuntimeException('A referência não usa o mecanismo inteiro previsto.');
        }
        $expected['column_type'] = $this->normalizeType($parent['column_type']);
        if ($reference[2] === 'set null' || $reference[3] === 'set null') { $expected['nullable'] = true; }
        return $expected;
    }

    private function preflightColumn(string $tableName, string $columnName, array $expected, bool $allowRecordedChange = false): void
    {
        $expected = $this->expectedColumnForReferences($columnName, $expected);
        if (!Schema::hasTable($tableName)) { return; } // Apenas a criação desta própria tabela.
        $current = $this->columnInfo($tableName, $columnName);
        $expected = $this->relaxRequiredColumnForExistingRows($tableName, $columnName, $current, $expected);
        if (!$allowRecordedChange && $this->recordedObjectIsComplete('column', $tableName . '.' . $columnName)) { return; }
        if (str_contains($expected['extra'], 'auto_increment') && ($current === null || stripos($current['extra'], 'auto_increment') === false)) {
            $primary = $this->indexDefinition($tableName, 'PRIMARY');
            if ($primary === null || count($primary['columns']) !== 1 || $primary['columns'][0]['name'] !== strtolower($columnName)) {
                throw new \RuntimeException('Tabela parcial sem PK compatível para ativar AUTO_INCREMENT: ' . $tableName . '.' . $columnName
                    . '. A criação implícita de uma chave não será presumida como propriedade desta migration.');
            }
        }
        if ($current === null) {
            return;
        }
        if ($this->sameColumn($current, $expected)) { return; }
        if ($current['generation_expression'] !== '') {
            throw new \RuntimeException('Coluna gerada não pode ser convertida por esta migration: ' . $tableName . '.' . $columnName);
        }
        $this->assertNoForeignDependency($tableName, $columnName);
        $this->assertColumnDataFits($tableName, $columnName, $current, $expected);
    }

    private function relaxRequiredColumnForExistingRows(
        string $tableName,
        string $columnName,
        ?array $current,
        array $expected
    ): array {
        if (
            $current === null
            && !(bool) ($expected['nullable'] ?? false)
            && ($expected['column_default'] ?? null) === null
            && !str_contains((string) ($expected['extra'] ?? ''), 'auto_increment')
            && Schema::hasTable($tableName)
            && DB::table($tableName)->exists()
        ) {
            $expected['nullable'] = true;

            if (function_exists('logger')) {
                logger()->warning('Migration forward-only relaxou coluna obrigatória ausente para nullable.', [
                    'migration' => self::MIGRATION_NAME,
                    'table' => $tableName,
                    'column' => $columnName,
                ]);
            }
        }

        return $expected;
    }

    private function preserveUnspecifiedAttributes(object $definition, ?array $current, array $expected): void
    {
        if ($current === null) { return; }
        if (!isset($expected['column_comment'])) { $definition->comment($current['column_comment']); }
        if (stripos($current['extra'], 'invisible') !== false) { $definition->invisible(); }
    }

    private function ensureColumn(string $tableName, string $columnName, \Closure $definition, array $expected, bool $allowRecordedChange = false): void
    {
        $this->ensureTableExists($tableName);
        $expected = $this->expectedColumnForReferences($columnName, $expected);
        $current = $this->columnInfo($tableName, $columnName);
        $expected = $this->relaxRequiredColumnForExistingRows($tableName, $columnName, $current, $expected);
        $key = $tableName . '.' . $columnName;
        $record = $this->auditRow('column', $key);
        if (!$allowRecordedChange && $this->recordedObjectIsComplete('column', $key)) { return; }
        if ($record && $record['state'] !== 'applied' && !$this->sameSnapshot($current, $record['resume_before'] ?? $record['before'])) {
            throw new \RuntimeException('Alteração de coluna interrompida: ' . $key);
        }
        if ($current !== null && $this->sameColumn($current, $expected)) { return; }
        $this->preflightColumn($tableName, $columnName, $expected, $allowRecordedChange);
        if (!$this->beginChange('column', $key, $current, $expected)) { return; }
        Schema::table($tableName, function (Blueprint $table) use ($definition, $current, $expected) {
            $column = $definition($table);
            if (isset(self::REFERENCE_COLUMNS[$column->name])) {
                preg_match('/^(tinyint|smallint|mediumint|int|bigint)( unsigned)?$/D', $expected['column_type'], $integer);
                $column->type = ['tinyint' => 'tinyInteger', 'smallint' => 'smallInteger', 'mediumint' => 'mediumInteger', 'int' => 'integer', 'bigint' => 'bigInteger'][$integer[1]];
                $column->unsigned(isset($integer[2]));
            }

            $column->nullable((bool) $expected['nullable']);

            $this->preserveUnspecifiedAttributes($column, $current, $expected);
            if ($current !== null) { $column->change(); }
        });
        $after = $this->columnInfo($tableName, $columnName);
        if ($after === null || !$this->sameColumn($after, $expected)) {
            throw new \RuntimeException('A definição resultante não corresponde à esperada: ' . $key);
        }
        $this->markCreated('column', $key);
    }

    private function indexes(string $tableName): array
    {
        $rows = DB::select(
            'SELECT INDEX_NAME AS index_name, NON_UNIQUE AS non_unique, SEQ_IN_INDEX AS seq,
             COLUMN_NAME AS column_name, SUB_PART AS sub_part, COLLATION AS sort_order,
             INDEX_TYPE AS index_type, IS_VISIBLE AS visible, EXPRESSION AS expression
             FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY INDEX_NAME, SEQ_IN_INDEX',
            [DB::getDatabaseName(), $tableName]
        );
        $indexes = [];
        foreach ($rows as $r) {
            $indexes[$r->index_name] ??= ['name' => $r->index_name, 'unique' => !(bool) $r->non_unique,
                'primary' => $r->index_name === 'PRIMARY', 'type' => strtoupper($r->index_type), 'visible' => $r->visible === 'YES', 'columns' => []];
            $indexes[$r->index_name]['columns'][] = ['name' => $r->column_name === null ? null : strtolower($r->column_name),
                'prefix' => $r->sub_part === null ? null : (int) $r->sub_part,
                'order' => $r->sort_order === 'D' ? 'DESC' : 'ASC', 'expression' => $r->expression];
        }
        return $indexes;
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return $this->indexDefinition($tableName, $indexName) !== null;
    }

    private function indexDefinition(string $tableName, string $indexName): ?array
    {
        foreach ($this->indexes($tableName) as $name => $definition) {
            if (strcasecmp($name, $indexName) === 0) { return $definition; }
        }
        return null;
    }

    private function sameIndex(array $actual, array $expected): bool
    {
        unset($actual['name'], $expected['name']);
        return $actual === $expected;
    }

    private function expectedIndex(string $indexName, array $columns, bool $unique): array
    {
        return ['name' => $indexName, 'unique' => $unique, 'primary' => $indexName === 'PRIMARY', 'type' => 'BTREE', 'visible' => true,
            'columns' => array_map(static fn ($c) => ['name' => strtolower($c), 'prefix' => null, 'order' => 'ASC', 'expression' => null], $columns)];
    }

    private function preflightIndex(string $tableName, string $indexName, array $columns, bool $unique = false): void
    {
        if (!Schema::hasTable($tableName)) { return; }
        $expected = $this->expectedIndex($indexName, $columns, $unique);
        $named = $this->indexDefinition($tableName, $indexName);
        if ($named !== null && !$this->sameIndex($named, $expected)) {
            throw new \RuntimeException('Índice homônimo com outra definição: ' . $tableName . '.' . $indexName
                . '. A substituição exige autorização para remoção; este up não faz DROP.');
        }
        if (!$unique) { return; }
        foreach ($columns as $c) { if (!Schema::hasColumn($tableName, $c)) { return; } }
        $quoted = array_map(fn ($c) => $this->quoteIdentifier($c), $columns);
        $notNull = implode(' AND ', array_map(fn ($c) => $c . ' IS NOT NULL', $quoted));
        $sql = 'SELECT 1 FROM ' . $this->quoteIdentifier($tableName) . ' WHERE ' . $notNull
            . ' GROUP BY ' . implode(', ', $quoted) . ' HAVING COUNT(*) > 1 LIMIT 1';
        if (DB::selectOne($sql) !== null) {
            throw new \RuntimeException('Duplicidade impede UNIQUE ' . $tableName . '.' . $indexName . '. Nenhum registro será apagado.');
        }
    }

    private function addIndexIfMissing(string $tableName, string $indexName, array $columns, bool $unique = false): void
    {
        $this->ensureTableExists($tableName);
        if ($this->recordedObjectIsComplete('index', $tableName . '.' . $indexName)) { return; }
        $this->preflightIndex($tableName, $indexName, $columns, $unique);
        $expected = $this->expectedIndex($indexName, $columns, $unique);
        foreach ($this->indexes($tableName) as $index) {
            if ($this->sameIndex($index, $expected)) { return; } // Comparação estrutural, não somente pelo nome.
        }
        foreach ($columns as $c) {
            if (!Schema::hasColumn($tableName, $c)) { throw new \RuntimeException('Coluna de índice ausente: ' . $tableName . '.' . $c); }
        }
        $key = $tableName . '.' . $indexName;
        if (!$this->beginChange('index', $key, null, $expected)) { return; }
        $indexesBefore = $this->indexes($tableName);
        $audit = $this->auditRow('index', $key);
        $audit['preexisting_indexes'] = $indexesBefore;
        $this->writeAudit('index', $key, $audit);
        Schema::table($tableName, function (Blueprint $table) use ($indexName, $columns, $unique) {
            if ($indexName === 'PRIMARY') { $table->primary($columns); }
            elseif ($unique) { $table->unique($columns, $indexName); }
            else { $table->index($columns, $indexName); }
        });
        // InnoDB pode substituir silenciosamente um índice implícito de FK ao receber outro que o cobre.
        // Reponha sua definição preexistente, sem registrá-lo como objeto novo desta migration.
        foreach ($indexesBefore as $previous) {
            $retained = $this->indexDefinition($tableName, $previous['name']);
            if ($retained !== null) {
                if (!$this->sameIndex($retained, $previous)) { throw new \RuntimeException('Índice preexistente modificado pelo DDL: ' . $previous['name']); }
                continue;
            }
            if ($previous['primary'] || $previous['type'] !== 'BTREE' || !$previous['visible']) {
                throw new \RuntimeException('Índice preexistente desapareceu e precisa de revisão: ' . $previous['name']);
            }
            $previousColumns = [];
            foreach ($previous['columns'] as $part) {
                if ($part['prefix'] !== null || $part['expression'] !== null || $part['order'] !== 'ASC') {
                    throw new \RuntimeException('Não é seguro reconstruir implicitamente o índice: ' . $previous['name']);
                }
                $previousColumns[] = $part['name'];
            }
            Schema::table($tableName, function (Blueprint $table) use ($previous, $previousColumns) {
                if ($previous['unique']) { $table->unique($previousColumns, $previous['name']); }
                else { $table->index($previousColumns, $previous['name']); }
            });
            $retained = $this->indexDefinition($tableName, $previous['name']);
            if ($retained === null || !$this->sameIndex($retained, $previous)) { throw new \RuntimeException('Não foi possível preservar o índice preexistente: ' . $previous['name']); }
        }
        $actual = $this->indexDefinition($tableName, $indexName);
        if ($actual === null || !$this->sameIndex($actual, $expected)) { throw new \RuntimeException('Índice resultante divergente: ' . $key); }
        $this->markCreated('index', $key);
    }

    private function foreigns(string $tableName): array
    {
        $rows = DB::select(
            'SELECT k.CONSTRAINT_NAME AS name, k.COLUMN_NAME AS column_name, k.REFERENCED_TABLE_SCHEMA AS ref_schema,
             k.REFERENCED_TABLE_NAME AS ref_table, k.REFERENCED_COLUMN_NAME AS ref_column,
             r.DELETE_RULE AS delete_rule, r.UPDATE_RULE AS update_rule
             FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r
             ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND r.TABLE_NAME = k.TABLE_NAME
             WHERE k.CONSTRAINT_SCHEMA = ? AND k.TABLE_NAME = ? AND k.REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY k.CONSTRAINT_NAME, k.ORDINAL_POSITION', [DB::getDatabaseName(), $tableName]
        );
        $result = [];
        foreach ($rows as $r) {
            $result[$r->name] ??= ['name' => $r->name, 'ref_schema' => $r->ref_schema, 'ref_table' => $r->ref_table,
                'columns' => [], 'ref_columns' => [], 'on_delete' => $this->normalizeRule($r->delete_rule), 'on_update' => $this->normalizeRule($r->update_rule)];
            $result[$r->name]['columns'][] = strtolower($r->column_name);
            $result[$r->name]['ref_columns'][] = strtolower($r->ref_column);
        }
        return $result;
    }

    private function normalizeRule(?string $rule): string
    {
        $rule = strtolower(trim($rule ?? 'restrict'));
        return $rule === 'no action' || $rule === '' ? 'restrict' : $rule;
    }

    private function foreignExists(string $tableName, string $foreignName): bool
    {
        return $this->foreignDefinition($tableName, $foreignName) !== null;
    }

    private function foreignDefinition(string $tableName, string $foreignName): ?array
    {
        foreach ($this->foreigns($tableName) as $name => $definition) {
            if (strcasecmp($name, $foreignName) === 0) { return $definition; }
        }
        return null;
    }

    private function sameForeign(array $actual, array $expected): bool
    {
        unset($actual['name'], $expected['name']);
        return $actual === $expected;
    }

    private function expectedForeign(string $name, string $column, string $referenceTable, string $referenceColumn, string $onDelete, string $onUpdate): array
    {
        return ['name' => $name, 'ref_schema' => DB::getDatabaseName(), 'ref_table' => $referenceTable,
            'columns' => [strtolower($column)], 'ref_columns' => [strtolower($referenceColumn)],
            'on_delete' => $this->normalizeRule($onDelete), 'on_update' => $this->normalizeRule($onUpdate)];
    }

    private function preflightForeign(string $tableName, string $foreignName, string $column, string $referenceTable,
        string $onDelete = 'cascade', string $referenceColumn = 'id', string $onUpdate = 'restrict'): void
    {
        $this->ensureTableExists($referenceTable);
        if (!Schema::hasColumn($referenceTable, $referenceColumn)) {
            throw new \RuntimeException('Coluna referenciada ausente: ' . $referenceTable . '.' . $referenceColumn);
        }
        $parentEngine = DB::selectOne('SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [DB::getDatabaseName(), $referenceTable]);
        if (!$parentEngine || strcasecmp((string) $parentEngine->engine, 'InnoDB') !== 0) {
            throw new \RuntimeException('A referência precisa usar InnoDB: ' . $referenceTable);
        }
        $indexed = false;
        foreach ($this->indexes($referenceTable) as $index) {
            if (($index['columns'][0]['name'] ?? null) === strtolower($referenceColumn)
                && $index['columns'][0]['prefix'] === null && $index['type'] === 'BTREE') { $indexed = true; break; }
        }
        if (!$indexed) { throw new \RuntimeException('Índice da referência ausente: ' . $referenceTable . '.' . $referenceColumn); }
        if (!Schema::hasTable($tableName)) { return; }
        $childEngine = DB::selectOne('SELECT ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [DB::getDatabaseName(), $tableName]);
        if (!$childEngine || strcasecmp((string) $childEngine->engine, 'InnoDB') !== 0) { throw new \RuntimeException('A tabela dependente não usa InnoDB: ' . $tableName); }
        $expected = $this->expectedForeign($foreignName, $column, $referenceTable, $referenceColumn, $onDelete, $onUpdate);
        $named = $this->foreignDefinition($tableName, $foreignName);
        if ($named !== null && !$this->sameForeign($named, $expected)) {
            throw new \RuntimeException('FK homônima divergente: ' . $tableName . '.' . $foreignName . '. Nenhuma constraint será descartada.');
        }
        if (!Schema::hasColumn($tableName, $column)) { return; }
        $a = $this->quoteIdentifier($tableName);
        $b = $this->quoteIdentifier($referenceTable);
        $c = $this->quoteIdentifier($column);
        $r = $this->quoteIdentifier($referenceColumn);
        if (DB::selectOne("SELECT 1 FROM {$a} AS child LEFT JOIN {$b} AS parent ON child.{$c} = parent.{$r} WHERE child.{$c} IS NOT NULL AND parent.{$r} IS NULL LIMIT 1") !== null) {
            throw new \RuntimeException('Registros órfãos impedem ' . $foreignName . '. Corrija a vinculação sem apagar registros.');
        }
    }

    private function addForeignIfMissing(string $tableName, string $foreignName, string $column, string $referenceTable,
        string $onDelete = 'cascade', string $referenceColumn = 'id', string $onUpdate = 'restrict'): void
    {
        $this->ensureTableExists($tableName);
        if ($this->recordedObjectIsComplete('foreign', $tableName . '.' . $foreignName)) { return; }
        $this->preflightForeign($tableName, $foreignName, $column, $referenceTable, $onDelete, $referenceColumn, $onUpdate);
        $expected = $this->expectedForeign($foreignName, $column, $referenceTable, $referenceColumn, $onDelete, $onUpdate);
        foreach ($this->foreigns($tableName) as $foreign) { if ($this->sameForeign($foreign, $expected)) { return; } }
        $this->normalizeForeignIntegerColumn($tableName, $column, $referenceTable, $referenceColumn, $onDelete === 'set null' || $onUpdate === 'set null');
        $covered = false;
        foreach ($this->indexes($tableName) as $index) {
            if (($index['columns'][0]['name'] ?? null) === strtolower($column)
                && $index['columns'][0]['prefix'] === null && $index['type'] === 'BTREE') { $covered = true; break; }
        }
        if (!$covered) { $this->addIndexIfMissing($tableName, 'idx_' . $tableName . '_' . $column, [$column]); }
        $key = $tableName . '.' . $foreignName;
        if (!$this->beginChange('foreign', $key, null, $expected)) { return; }
        Schema::table($tableName, function (Blueprint $table) use ($foreignName, $column, $referenceTable, $referenceColumn, $onDelete, $onUpdate) {
            $table->foreign($column, $foreignName)->references($referenceColumn)->on($referenceTable)->onDelete($onDelete)->onUpdate($onUpdate);
        });
        $actual = $this->foreignDefinition($tableName, $foreignName);
        if ($actual === null || !$this->sameForeign($actual, $expected)) { throw new \RuntimeException('FK resultante divergente: ' . $key); }
        $this->markCreated('foreign', $key);
    }

    private function normalizeForeignIntegerColumn(string $tableName, string $column, string $referenceTable,
        string $referenceColumn = 'id', bool $mustBeNullable = false): void
    {
        $current = $this->columnInfo($tableName, $column);
        $referenced = $this->columnInfo($referenceTable, $referenceColumn);
        if ($current === null || $referenced === null) { throw new \RuntimeException('Metadados de FK ausentes.'); }
        $type = $this->normalizeType($referenced['column_type']);
        if (!preg_match('/^(tinyint|smallint|mediumint|int|bigint)( unsigned)?$/D', $type, $m)) {
            throw new \RuntimeException('Tipo de FK fora do mecanismo inteiro existente: ' . $type);
        }
        $desired = $current;
        $desired['column_type'] = $type;
        $desired['data_type'] = $m[1];
        if ($mustBeNullable) { $desired['nullable'] = true; }
        if ($this->sameColumn($current, $desired)) { return; }
        $this->ensureColumn($tableName, $column, function (Blueprint $table) use ($column, $desired, $m) {
            $method = ['tinyint' => 'tinyInteger', 'smallint' => 'smallInteger', 'mediumint' => 'mediumInteger', 'int' => 'integer', 'bigint' => 'bigInteger'][$m[1]];
            $definition = $table->{$method}($column)->unsigned(isset($m[2]))->nullable($desired['nullable']);
            if ($desired['column_default'] !== null) {
                $default = stripos($desired['extra'], 'DEFAULT_GENERATED') !== false ? DB::raw($desired['column_default']) : $desired['column_default'];
                $definition->default($default);
            }
            $definition->comment($desired['column_comment']);
            if (stripos($desired['extra'], 'auto_increment') !== false) { $definition->autoIncrement(); }
            return $definition;
        }, $desired, true);
    }

    private function assertNoForeignDependency(string $tableName, string $columnName): void
    {
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME AS name FROM information_schema.KEY_COLUMN_USAGE
             WHERE CONSTRAINT_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL
             AND ((TABLE_NAME = ? AND COLUMN_NAME = ?) OR (REFERENCED_TABLE_NAME = ? AND REFERENCED_COLUMN_NAME = ?)) LIMIT 1',
            [DB::getDatabaseName(), $tableName, $columnName, $tableName, $columnName]
        );
        if ($row !== null) {
            throw new \RuntimeException('Alteração depende de FK ativa (' . $row->name . '): ' . $tableName . '.' . $columnName
                . '. A migration não remove proteções nem altera a tabela referenciada.');
        }
    }

    private function integerRange(string $base, bool $unsigned): array
    {
        return match ($base . ($unsigned ? ' unsigned' : '')) {
            'tinyint' => ['-128', '127'], 'tinyint unsigned' => ['0', '255'],
            'smallint' => ['-32768', '32767'], 'smallint unsigned' => ['0', '65535'],
            'mediumint' => ['-8388608', '8388607'], 'mediumint unsigned' => ['0', '16777215'],
            'int' => ['-2147483648', '2147483647'], 'int unsigned' => ['0', '4294967295'],
            'bigint' => ['-9223372036854775808', '9223372036854775807'],
            'bigint unsigned' => ['0', '18446744073709551615'],
            default => throw new \RuntimeException('Tipo inteiro não suportado: ' . $base),
        };
    }

    private function assertColumnDataFits(string $tableName, string $columnName, array $current, array $expected): void
    {
        $table = $this->quoteIdentifier($tableName);
        $column = $this->quoteIdentifier($columnName);
        if (!$expected['nullable'] && DB::selectOne("SELECT 1 FROM {$table} WHERE {$column} IS NULL LIMIT 1") !== null) {
            throw new \RuntimeException('Valores NULL impedem a definição obrigatória de ' . $tableName . '.' . $columnName);
        }
        $type = $this->normalizeType($expected['column_type']);
        $currentType = $this->normalizeType($current['column_type']);
        $predicate = null;
        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint)( unsigned)?$/D', $type, $m)) {
            if (!preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal)\b/', $currentType)
                && DB::selectOne("SELECT 1 FROM {$table} WHERE {$column} IS NOT NULL AND CAST({$column} AS CHAR) NOT REGEXP '^[+-]?[0-9]+$' LIMIT 1") !== null) {
                throw new \RuntimeException('Conteúdo não inteiro em ' . $tableName . '.' . $columnName);
            }
            [$min, $max] = $this->integerRange($m[1], isset($m[2]));
            $predicate = "CAST({$column} AS DECIMAL(65,30)) < {$min} OR CAST({$column} AS DECIMAL(65,30)) > {$max} OR CAST({$column} AS DECIMAL(65,30)) <> TRUNCATE(CAST({$column} AS DECIMAL(65,30)),0)";
        } elseif (preg_match('/^decimal\((\d+),(\d+)\)( unsigned)?$/D', $type, $m)) {
            if (!preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal)\b/', $currentType)
                && DB::selectOne("SELECT 1 FROM {$table} WHERE {$column} IS NOT NULL AND CAST({$column} AS CHAR) NOT REGEXP '^[+-]?[0-9]+([.][0-9]+)?$' LIMIT 1") !== null) {
                throw new \RuntimeException('Conteúdo não decimal em ' . $tableName . '.' . $columnName);
            }
            $scale = (int) $m[2];
            $integerDigits = (int) $m[1] - $scale;
            $max = ($integerDigits > 0 ? str_repeat('9', $integerDigits) : '0') . ($scale ? '.' . str_repeat('9', $scale) : '');
            $min = isset($m[3]) ? '0' : '-' . $max;
            $predicate = "CAST({$column} AS DECIMAL(65,30)) < {$min} OR CAST({$column} AS DECIMAL(65,30)) > {$max} OR CAST({$column} AS DECIMAL(65,30)) <> ROUND(CAST({$column} AS DECIMAL(65,30)),{$scale})";
        } elseif (preg_match('/^(?:varchar|char)\((\d+)\)$/D', $type, $m)) {
            $predicate = 'CHAR_LENGTH(' . $column . ') > ' . (int) $m[1];
        } elseif (str_starts_with($type, 'enum(')) {
            preg_match_all("/'((?:[^'\\\\]|\\\\.|'')*)'/", $type, $matches);
            $values = array_map(static fn ($v) => str_replace("''", "'", stripcslashes($v)), $matches[1]);
            if ($values === []) { throw new \RuntimeException('ENUM inválido.'); }
            $placeholders = implode(',', array_fill(0, count($values), '?'));
            if (DB::selectOne("SELECT 1 FROM {$table} WHERE {$column} IS NOT NULL AND CAST({$column} AS BINARY) NOT IN ({$placeholders}) LIMIT 1", $values) !== null) {
                throw new \RuntimeException('Estado não contemplado no ENUM de ' . $tableName . '.' . $columnName);
            }
        } elseif (in_array($type, ['tinytext', 'text', 'mediumtext', 'longtext'], true)) {
            $max = ['tinytext' => '255', 'text' => '65535', 'mediumtext' => '16777215', 'longtext' => '4294967295'][$type];
            $predicate = "OCTET_LENGTH({$column}) > {$max}";
        } elseif ($type === 'json') {
            $predicate = "JSON_VALID({$column}) = 0";
        } elseif ($type !== $currentType) {
            throw new \RuntimeException('Conversão não comprovadamente reversível: ' . $tableName . '.' . $columnName . ' (' . $currentType . ' -> ' . $type . ').');
        }
        if ($predicate !== null && DB::selectOne("SELECT 1 FROM {$table} WHERE {$column} IS NOT NULL AND ({$predicate}) LIMIT 1") !== null) {
            throw new \RuntimeException('Conversão perderia valor, faixa ou precisão em ' . $tableName . '.' . $columnName);
        }
    }

    private function tableSnapshot(string $tableName): ?array
    {
        if (!Schema::hasTable($tableName)) { return null; }
        $columns = [];
        foreach (Schema::getColumnListing($tableName) as $c) { $columns[$c] = $this->columnInfo($tableName, $c); }
        ksort($columns);
        $options = (array) DB::selectOne('SELECT ENGINE AS engine, TABLE_COLLATION AS collation_name, TABLE_COMMENT AS comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [DB::getDatabaseName(), $tableName]);
        return ['columns' => $columns, 'indexes' => $this->indexes($tableName), 'foreigns' => $this->foreigns($tableName), 'options' => $options];
    }

    private function snapshotObject(string $type, string $name): ?array
    {
        if ($type === 'table') { return $this->tableSnapshot($name); }
        [$table, $object] = explode('.', $name, 2);
        if (!Schema::hasTable($table)) { return null; }
        return match ($type) {
            'column' => $this->columnInfo($table, $object),
            'index' => $this->indexDefinition($table, $object),
            'foreign' => $this->foreignDefinition($table, $object),
            default => throw new \RuntimeException('Tipo de auditoria inesperado: ' . $type),
        };
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (!$this->indexExists($tableName, $indexName)) { return; }
        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            if ($indexName === 'PRIMARY') { $table->dropPrimary(); }
            else { $table->dropIndex($indexName); }
        });
    }

    private function dropForeignIfExists(string $tableName, string $foreignName): void
    {
        if (!$this->foreignExists($tableName, $foreignName)) { return; }
        Schema::table($tableName, function (Blueprint $table) use ($foreignName) { $table->dropForeign($foreignName); });
    }

    private function dropColumnIfExists(string $tableName, string $columnName): void
    {
        if (!Schema::hasColumn($tableName, $columnName)) { return; }
        Schema::table($tableName, function (Blueprint $table) use ($columnName) { $table->dropColumn($columnName); });
    }

    private function rollbackRecordedChanges(): void
    {
        $rows = DB::table('migration_merge_audit')->where('migration_name', $this->migrationName())->orderByRaw("CASE object_type WHEN 'foreign' THEN 0 WHEN 'index' THEN 1 WHEN 'column' THEN 2 ELSE 3 END")->orderByDesc('id')->get();
        $ownedTable = $this->auditRow('table', self::TABLE_NAME);
        if ($ownedTable !== null && $ownedTable['before'] === null) {
            if ($ownedTable['state'] === 'pending') {
                throw new \RuntimeException('Criação de tabela interrompida: ownership não confirmado. Revise a auditoria.');
            }
            if (!Schema::hasTable(self::TABLE_NAME) && $ownedTable['state'] === 'reverting') {
                DB::table('migration_merge_audit')->where('migration_name', $this->migrationName())->delete();
                return;
            }
            foreach ($rows as $row) {
                $meta = json_decode($row->metadata_json, true, 512, JSON_THROW_ON_ERROR);
                if ($meta['state'] === 'pending') { throw new \RuntimeException('DDL pendente/ambíguo: ' . $row->object_name); }
            }
            if (!$this->sameSnapshot($this->tableSnapshot(self::TABLE_NAME), $ownedTable['after'])) {
                throw new \RuntimeException('A tabela criada foi modificada por outro processo; rollback bloqueado: ' . self::TABLE_NAME);
            }
            $external = DB::selectOne('SELECT TABLE_NAME AS name FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME = ? AND NOT (TABLE_SCHEMA = ? AND TABLE_NAME = ?) LIMIT 1',
                [DB::getDatabaseName(), self::TABLE_NAME, DB::getDatabaseName(), self::TABLE_NAME]);
            if ($external) { throw new \RuntimeException('Faça rollback primeiro da tabela dependente ' . $external->name); }
            $view = DB::selectOne('SELECT VIEW_NAME AS name FROM information_schema.VIEW_TABLE_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? LIMIT 1', [DB::getDatabaseName(), self::TABLE_NAME]);
            if ($view) { throw new \RuntimeException('View dependente externa impede rollback da tabela: ' . $view->name); }
            $trigger = DB::selectOne('SELECT TRIGGER_NAME AS name FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = ? AND EVENT_OBJECT_TABLE = ? LIMIT 1', [DB::getDatabaseName(), self::TABLE_NAME]);
            if ($trigger) { throw new \RuntimeException('Trigger criado por outro processo impede rollback: ' . $trigger->name); }
            $ownedTable['state'] = 'reverting';
            $this->writeAudit('table', self::TABLE_NAME, $ownedTable);
            // Outgoing FKs são removidas pela própria operação DROP TABLE, com checks ativos.
            Schema::dropIfExists(self::TABLE_NAME);
            DB::table('migration_merge_audit')->where('migration_name', $this->migrationName())->delete();
            return;
        }
        foreach ($rows as $row) {
            $meta = json_decode((string) $row->metadata_json, true, 512, JSON_THROW_ON_ERROR);
            $actual = $this->snapshotObject($row->object_type, $row->object_name);
            if ($meta['state'] === 'reverting' && $this->sameSnapshot($actual, $meta['before'])) {
                $this->forgetCreated($row->object_type, $row->object_name);
                continue;
            }
            if (!in_array($meta['state'], ['applied', 'reverting'], true) || !$this->sameSnapshot($actual, $meta['after'])) {
                throw new \RuntimeException('Rollback não pode presumir ownership/estado de ' . $row->object_name);
            }
            [$table, $object] = explode('.', $row->object_name, 2);
            if ($row->object_type === 'column' && $meta['before'] !== null) {
                $this->assertNoForeignDependency($table, $object);
                $this->assertColumnDataFits($table, $object, $actual, $meta['before']);
            }
            if ($row->object_type === 'column' && $meta['before'] === null) {
                $this->assertNoForeignDependency($table, $object);
                foreach ($this->indexes($table) as $index) {
                    foreach ($index['columns'] as $part) {
                        if ($part['name'] === strtolower($object) || $part['expression'] !== null) {
                            throw new \RuntimeException('Índice remanescente depende da coluna criada: ' . $index['name'] . '. Reverta a dependência primeiro.');
                        }
                    }
                }
            }
            $meta['state'] = 'reverting';
            $this->writeAudit($row->object_type, $row->object_name, $meta);
            if ($row->object_type === 'foreign') { $this->dropForeignIfExists($table, $object); }
            elseif ($row->object_type === 'index') { $this->dropIndexIfExists($table, $object); }
            elseif ($row->object_type === 'column' && $meta['before'] === null) { $this->dropColumnIfExists($table, $object); }
            elseif ($row->object_type === 'column') {
                // SQL pontual para repor a definição completa capturada do próprio MySQL.
                DB::statement('ALTER TABLE ' . $this->quoteIdentifier($table) . ' MODIFY COLUMN '
                    . $this->quoteIdentifier($object) . ' ' . $meta['before']['raw']);
            } else { throw new \RuntimeException('Operação não reconhecida no rollback.'); }
            if (!$this->sameSnapshot($this->snapshotObject($row->object_type, $row->object_name), $meta['before'])) {
                throw new \RuntimeException('Rollback não restaurou a definição original: ' . $row->object_name);
            }
            $this->forgetCreated($row->object_type, $row->object_name);
        }
    }
};
