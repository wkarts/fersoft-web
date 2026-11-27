<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new \RuntimeException('Esta migration requer MySQL.');
        }

        if (!Schema::hasTable('item_compras')) {
            throw new \RuntimeException('Tabela esperada ausente: item_compras');
        }

        $this->applyColumnDefinition('item_compras', 'cst_icms', 'varchar(10) DEFAULT NULL');

        $this->applyColumnDefinition('item_compras', 'cst_ipi', 'varchar(10) DEFAULT NULL');

        $this->applyColumnDefinition('item_compras', 'cst_pis', 'varchar(10) DEFAULT NULL');

        $this->applyColumnDefinition('item_compras', 'cst_cofins', 'varchar(10) DEFAULT NULL');

        $this->ensureColumn('item_compras', 'v_receita', function (Blueprint $table) {
            $table->decimal('v_receita', 18, 6)->nullable();
        });
    }

    public function down()
    {
        // Não volta para VARCHAR(3): isso poderia truncar registros gravados após a ampliação.
        return;
    }

    protected function ensureColumn(string $tableName, string $columnName, \Closure $definition): void
    {
        if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($definition) {
            $definition($table);
        });
    }

    private function applyColumnDefinition(string $tableName, string $columnName, string $definition): void
    {
        $definitions = [
            'cst_icms' => [
                'sql' => 'varchar(10) DEFAULT NULL',
                'expected' => [
                    'COLUMN_TYPE' => 'varchar(10)',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => null,
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => 'utf8mb4',
                    'COLLATION_NAME' => 'utf8mb4_unicode_ci',
                    'COLUMN_COMMENT' => '',
                ],
                'previous' => [
                    'COLUMN_TYPE' => 'varchar(10)',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => null,
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => 'utf8mb4',
                    'COLLATION_NAME' => 'utf8mb4_unicode_ci',
                    'COLUMN_COMMENT' => '',
                ],
                'guard' => 'SELECT 1 AS found FROM `item_compras` WHERE CHAR_LENGTH(`cst_icms`) > 10 LIMIT 1',
            ],
            'cst_ipi' => [
                'sql' => 'varchar(10) DEFAULT NULL',
                'expected' => [
                    'COLUMN_TYPE' => 'varchar(10)',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => null,
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => 'utf8mb4',
                    'COLLATION_NAME' => 'utf8mb4_unicode_ci',
                    'COLUMN_COMMENT' => '',
                ],
                'previous' => [
                    'COLUMN_TYPE' => 'varchar(10)',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => null,
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => 'utf8mb4',
                    'COLLATION_NAME' => 'utf8mb4_unicode_ci',
                    'COLUMN_COMMENT' => '',
                ],
                'guard' => 'SELECT 1 AS found FROM `item_compras` WHERE CHAR_LENGTH(`cst_ipi`) > 10 LIMIT 1',
            ],
            'cst_pis' => [
                'sql' => 'varchar(10) DEFAULT NULL',
                'expected' => [
                    'COLUMN_TYPE' => 'varchar(10)',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => null,
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => 'utf8mb4',
                    'COLLATION_NAME' => 'utf8mb4_unicode_ci',
                    'COLUMN_COMMENT' => '',
                ],
                'previous' => [
                    'COLUMN_TYPE' => 'varchar(10)',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => null,
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => 'utf8mb4',
                    'COLLATION_NAME' => 'utf8mb4_unicode_ci',
                    'COLUMN_COMMENT' => '',
                ],
                'guard' => 'SELECT 1 AS found FROM `item_compras` WHERE CHAR_LENGTH(`cst_pis`) > 10 LIMIT 1',
            ],
            'cst_cofins' => [
                'sql' => 'varchar(10) DEFAULT NULL',
                'expected' => [
                    'COLUMN_TYPE' => 'varchar(10)',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => null,
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => 'utf8mb4',
                    'COLLATION_NAME' => 'utf8mb4_unicode_ci',
                    'COLUMN_COMMENT' => '',
                ],
                'previous' => [
                    'COLUMN_TYPE' => 'varchar(10)',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => null,
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => 'utf8mb4',
                    'COLLATION_NAME' => 'utf8mb4_unicode_ci',
                    'COLUMN_COMMENT' => '',
                ],
                'guard' => 'SELECT 1 AS found FROM `item_compras` WHERE CHAR_LENGTH(`cst_cofins`) > 10 LIMIT 1',
            ],
        ];
        $definitions = [$columnName => $definitions[$columnName]];
        if ($definitions[$columnName]['sql'] !== $definition) {
            throw new \RuntimeException("Definição não prevista para {$tableName}.{$columnName}.");
        }
        $rows = DB::select(
            'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, '
            . 'CHARACTER_SET_NAME, COLLATION_NAME, COLUMN_COMMENT FROM information_schema.COLUMNS '
            . 'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [DB::getDatabaseName(), $tableName],
            false
        );
        $columns = [];
        foreach ($rows as $row) {
            $columns[$row->COLUMN_NAME] = $row;
        }

        $alterations = [];
        $requiresStrictMode = false;
        foreach ($definitions as $name => $definition) {
            $current = $columns[$name] ?? null;
            if ($current === null) {
                if ($definition['previous'] !== null) {
                    throw new \RuntimeException($tableName . ".{$name}: coluna original ausente.");
                }
                $expected = $definition['expected'];
                if (str_contains($expected['EXTRA'], 'auto_increment')
                    || ($expected['IS_NULLABLE'] === 'NO' && $expected['COLUMN_DEFAULT'] === null
                        && DB::table($tableName)->exists())) {
                    throw new \RuntimeException($tableName . ".{$name}: inclusão exige revisão dos dados existentes.");
                }
                $alterations[] = "ADD COLUMN `{$name}` " . $definition['sql'];
                continue;
            }
            // Capacidade mínima de dez caracteres para instalações novas e já atualizadas.
            // Preserva VARCHAR maior; nunca reduz tamanho nem modifica os códigos existentes.
            // Os demais atributos continuam sujeitos às verificações existentes abaixo.
            if (preg_match('/^varchar\((\d+)\)$/iD', trim((string) $current->COLUMN_TYPE), $length)) {
                $currentLength = (int) $length[1];
                $desiredLength = max(10, $currentLength);
                $definition['expected']['COLUMN_TYPE'] = "varchar({$desiredLength})";
                $definition['sql'] = "varchar({$desiredLength}) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL";
                $definition['guard'] = "SELECT 1 AS found FROM `{$tableName}` WHERE CHAR_LENGTH(`{$name}`) > {$desiredLength} LIMIT 1";
            }

            // Preserva NULLs existentes ao ampliar ou atualizar a definição da coluna.
            if ($current->IS_NULLABLE === 'YES' && $definition['expected']['IS_NULLABLE'] === 'NO') {
                $definition['expected']['IS_NULLABLE'] = 'YES';
                $definition['sql'] = preg_replace('/\bNOT NULL\b/i', 'NULL', $definition['sql']);
            }
            if ($this->columnMatches($current, $definition['expected'])) {
                continue;
            }
            $matchesPrevious = $definition['previous'] !== null
                && $this->columnMatches($current, $definition['previous']);

            // Aceita os tamanhos VARCHAR legados, inclusive os três caracteres
            // aplicados pela migration anterior. Os demais atributos são conferidos.
            if (!$matchesPrevious && $definition['previous'] !== null
                && preg_match('/^varchar\([1-9]\d*\)$/iD', trim((string) $current->COLUMN_TYPE))) {
                $legacyDefinition = $definition['previous'];
                $legacyDefinition['COLUMN_TYPE'] = $current->COLUMN_TYPE;
                $legacyDefinition['IS_NULLABLE'] = $current->IS_NULLABLE;
                $matchesPrevious = $this->columnMatches($current, $legacyDefinition);
            }

            // Diferenças apenas de nulabilidade não impedem uma alteração compatível.
            if (!$matchesPrevious && in_array($current->IS_NULLABLE, ['YES', 'NO'], true)) {
                foreach ([$definition['previous'], $definition['expected']] as $candidate) {
                    if ($candidate === null) {
                        continue;
                    }
                    $candidate['IS_NULLABLE'] = $current->IS_NULLABLE;
                    if ($current->IS_NULLABLE === 'YES' && $current->COLUMN_DEFAULT === null) {
                        $candidate['COLUMN_DEFAULT'] = null;
                    }
                    if ($this->columnMatches($current, $candidate)) {
                        $matchesPrevious = true;
                        break;
                    }
                }
            }

            if (!$matchesPrevious) {
                throw new \RuntimeException($tableName . ".{$name}: definição incompatível com as variantes conhecidas. Nenhuma substituição automática.");
            }

            // Alterações sensíveis exigem valores compatíveis com o tipo/collation da origem.
            if (isset($definition['guard']) && DB::selectOne($definition['guard'], [], false) !== null) {
                throw new \RuntimeException($tableName . ".{$name}: há valores incompatíveis com o tipo da origem; nenhum registro foi corrigido ou excluído.");
            }
            $requiresStrictMode = $requiresStrictMode || isset($definition['guard']);
            $alterations[] = "MODIFY COLUMN `{$name}` " . $definition['sql'];
        }
        if ($alterations === []) {
            return;
        }

        $sql = 'ALTER TABLE `' . $tableName . '` ' . implode(', ', $alterations);
        if (!$requiresStrictMode) {
            DB::statement($sql);
            return;
        }

        // Usa a conexão de escrita para consultar e ajustar apenas esta sessão.
        $connection = DB::connection();
        $mode = $connection->selectOne('SELECT @@SESSION.sql_mode AS modes', [], false);
        if ($mode === null || !isset($mode->modes)) {
            throw new \RuntimeException('Não foi possível consultar SQL_MODE da sessão de escrita.');
        }
        $originalMode = (string) $mode->modes;
        $alreadyStrict = preg_match('/(?:^|,)\s*(?:STRICT_ALL_TABLES|STRICT_TRANS_TABLES)\s*(?:,|$)/i', $originalMode) === 1;
        $modeChanged = false;

        try {
            if (!$alreadyStrict) {
                $strictMode = trim($originalMode, " ,\t\n\r\0\x0B");
                $strictMode = $strictMode === '' ? 'STRICT_ALL_TABLES' : $strictMode . ',STRICT_ALL_TABLES';
                $connection->statement('SET SESSION sql_mode = ?', [$strictMode]);
                $modeChanged = true;
            }
            $connection->statement($sql);
        } finally {
            // Restaura inclusive quando o ALTER falha; não altera configuração global.
            if ($modeChanged) {
                $connection->statement('SET SESSION sql_mode = ?', [$originalMode]);
            }
        }
    }

    private function columnMatches(object $current, array $expected): bool
    {
        foreach ($expected as $key => $value) {
            $actual = $current->{$key} ?? null;
            if ($key === 'COLUMN_TYPE') {
                if ($this->normalizeType((string) $actual) !== $this->normalizeType((string) $value)) {
                    return false;
                }
            } elseif ($key === 'EXTRA') {
                // MySQL 8 acrescenta DEFAULT_GENERATED a defaults de timestamp.
                $normalize = static fn ($v) => strtolower(trim(preg_replace('/\s+/', ' ', str_ireplace(['DEFAULT_GENERATED', 'CURRENT_TIMESTAMP()'], ['', 'CURRENT_TIMESTAMP'], (string) $v))));
                if ($normalize($actual) !== $normalize($value)) {
                    return false;
                }
            } elseif ($key === 'COLUMN_DEFAULT') {
                $normalize = static fn ($v) => $v === null ? null : preg_replace('/^current_timestamp(?:\(\))?$/i', 'CURRENT_TIMESTAMP', (string) $v);
                if ($normalize($actual) !== $normalize($value)) {
                    return false;
                }
            } elseif (($actual === null ? null : (string) $actual) !== ($value === null ? null : (string) $value)) {
                return false;
            }
        }
        return true;
    }

    private function normalizeType(string $type): string
    {
        $parts = preg_split("/('(?:''|\\\\.|[^'\\\\])*')/", $type, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $index => &$part) {
            if ($index % 2 === 0) {
                $part = preg_replace('/\s+/', '', strtolower($part));
            }
        }
        unset($part);
        // Largura de exibição de inteiros não altera sua capacidade.
        return preg_replace('/^(tinyint|smallint|mediumint|int|bigint)\(\d+\)/', '$1', implode('', $parts));
    }

};

