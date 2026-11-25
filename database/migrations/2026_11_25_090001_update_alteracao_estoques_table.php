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

        if (!Schema::hasTable('alteracao_estoques')) {
            throw new \RuntimeException('Tabela esperada ausente: alteracao_estoques');
        }

        if (DB::selectOne('SELECT 1 AS found FROM `alteracao_estoques` WHERE `filial_id` < 0 OR `filial_id` > 4294967295 LIMIT 1') !== null) {
            throw new \RuntimeException('alteracao_estoques.filial_id: valores incompatíveis; nenhum registro foi alterado.');
        }

        $this->applyColumnDefinition('alteracao_estoques', 'filial_id', 'int UNSIGNED DEFAULT NULL');

        $this->addForeignIfMissing('alteracao_estoques', 'fk_alteracao_estoques_filial', ['filial_id'], 'filials', ['id'], 'set null', null);
    }

    public function down()
    {
        // Migration somente evolutiva.
        return;
    }

    private function applyColumnDefinition(string $tableName, string $columnName, string $definition): void
    {
        $definitions = [
            'filial_id' => [
                'sql' => 'int UNSIGNED DEFAULT NULL',
                'expected' => [
                    'COLUMN_TYPE' => 'int UNSIGNED',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => null,
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => null,
                    'COLLATION_NAME' => null,
                    'COLUMN_COMMENT' => '',
                ],
                'previous' => [
                    'COLUMN_TYPE' => 'bigint UNSIGNED',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => null,
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => null,
                    'COLLATION_NAME' => null,
                    'COLUMN_COMMENT' => '',
                ],
                'guard' => 'SELECT 1 AS found FROM `alteracao_estoques` WHERE `filial_id` < 0 OR `filial_id` > 4294967295 LIMIT 1',
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
                throw new \RuntimeException($tableName . ".{$name}: definição incompatível com a alteração prevista. Atual: "
                    . json_encode($current, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    . '. Nenhuma substituição automática.');
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

    private function addForeignIfMissing(string $tableName, string $foreignName, array $columns, string $referenceTable, array $referenceColumns, ?string $onDelete = null, ?string $onUpdate = null): void
    {
        $column = $columns[0];
        $referenceColumn = $referenceColumns[0];
        $onDelete = strtoupper($onDelete ?? 'RESTRICT');
        $onUpdate = strtoupper($onUpdate ?? 'RESTRICT');
        $rows = DB::select(
            'SELECT k.CONSTRAINT_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME, r.DELETE_RULE, r.UPDATE_RULE '
            . 'FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r '
            . 'ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.TABLE_NAME = k.TABLE_NAME AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME '
            . 'WHERE k.TABLE_SCHEMA = ? AND k.TABLE_NAME = ? AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY k.CONSTRAINT_NAME, k.ORDINAL_POSITION',
            [DB::getDatabaseName(), $tableName]
        );
        $foreigns = [];
        foreach ($rows as $row) {
            $foreigns[$row->CONSTRAINT_NAME][] = [
                $row->COLUMN_NAME, $row->REFERENCED_TABLE_NAME, $row->REFERENCED_COLUMN_NAME,
                $row->DELETE_RULE === 'NO ACTION' ? 'RESTRICT' : $row->DELETE_RULE,
                $row->UPDATE_RULE === 'NO ACTION' ? 'RESTRICT' : $row->UPDATE_RULE,
            ];
        }
        $expected = [[$column, $referenceTable, $referenceColumn, $onDelete, $onUpdate]];
        if (isset($foreigns[$foreignName])) {
            if ($foreigns[$foreignName] !== $expected) {
                throw new \RuntimeException($tableName . ": FK {$foreignName} existente com outra definição; preservada.");
            }
            return;
        }
        if (in_array($expected, $foreigns, true)) {
            return;
        }
        if (!Schema::hasTable($referenceTable)) {
            throw new \RuntimeException("Tabela referenciada ausente: {$referenceTable}");
        }
        $metadata = static fn ($table, $field) => DB::selectOne(
            'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT, EXTRA, COLUMN_KEY FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), $table, $field],
            false
        );
        $local = $metadata($tableName, $column);
        $parent = $metadata($referenceTable, $referenceColumn);
        $integerType = static fn ($type) => preg_replace('/\(\d+\)/', '', strtolower(trim($type)));
        if ($local === null || $parent === null || $integerType($local->COLUMN_TYPE) !== $integerType($parent->COLUMN_TYPE)) {
            throw new \RuntimeException($tableName . ": tipos incompatíveis para FK {$foreignName}; nenhuma conversão implícita.");
        }
        if (DB::selectOne('SELECT 1 AS found FROM `' . $tableName . '` c LEFT JOIN `' . $referenceTable
            . '` p ON p.`' . $referenceColumn . '` = c.`' . $column . '` WHERE c.`' . $column
            . '` IS NOT NULL AND p.`' . $referenceColumn . '` IS NULL LIMIT 1') !== null) {
            throw new \RuntimeException($tableName . ": registros órfãos impedem a FK {$foreignName}; nenhum registro foi alterado.");
        }
        // SET NULL exige coluna nullable, inclusive em tabelas já existentes.
        if (($onDelete === 'SET NULL' || $onUpdate === 'SET NULL') && $local->IS_NULLABLE !== 'YES') {
            if (!preg_match('/^(?:tinyint|smallint|mediumint|int|bigint)(?:\(\d+\))?(?: unsigned)?$/i', trim($local->COLUMN_TYPE))
                || trim((string) $local->EXTRA) !== '' || $local->COLUMN_KEY === 'PRI') {
                throw new \RuntimeException($tableName . ": coluna {$column} exige revisão antes de permitir NULL.");
            }
            $quote = static function ($value): string {
                $quoted = DB::connection()->getPdo()->quote((string) $value);
                if ($quoted === false) {
                    throw new \RuntimeException('Não foi possível preparar atributo da coluna da FK.');
                }
                return $quoted;
            };
            $default = $local->COLUMN_DEFAULT === null ? 'NULL' : $quote($local->COLUMN_DEFAULT);
            DB::statement('ALTER TABLE `' . $tableName . '` MODIFY COLUMN `' . $column . '` '
                . $local->COLUMN_TYPE . ' NULL DEFAULT ' . $default
                . ' COMMENT ' . $quote($local->COLUMN_COMMENT));
        }
        Schema::table($tableName, function (Blueprint $table) use ($foreignName, $columns, $referenceTable, $referenceColumns, $onDelete, $onUpdate) {
            $table->foreign($columns, $foreignName)
                ->references($referenceColumns)->on($referenceTable)
                ->onDelete(strtolower($onDelete))->onUpdate(strtolower($onUpdate));
        });
    }

};
