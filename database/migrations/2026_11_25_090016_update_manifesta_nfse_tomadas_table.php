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

        if (!Schema::hasTable('manifesta_nfse_tomadas')) {
            throw new \RuntimeException('Tabela esperada ausente: manifesta_nfse_tomadas');
        }

        if (DB::selectOne('SELECT 1 AS found FROM `manifesta_nfse_tomadas` WHERE `chave` IS NOT NULL GROUP BY `chave` COLLATE utf8mb4_general_ci HAVING COUNT(*) > 1 LIMIT 1') !== null) {
            throw new \RuntimeException('manifesta_nfse_tomadas.chave: valores incompatíveis; nenhum registro foi alterado.');
        }

        $this->applyColumnDefinition('manifesta_nfse_tomadas', 'chave', 'varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL');

        $this->applyColumnDefinition('manifesta_nfse_tomadas', 'nsu', 'varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT \'0\'');

        $this->applyColumnDefinition('manifesta_nfse_tomadas', 'situacao', 'varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT \'NORMAL\'');

        $this->ensureTableCollation('manifesta_nfse_tomadas', 'utf8mb4_general_ci');
    }

    public function down()
    {
        // Migration somente evolutiva.
        return;
    }

    private function applyColumnDefinition(string $tableName, string $columnName, string $definition): void
    {
        $definitions = [
            'chave' => [
                'sql' => 'varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL',
                'expected' => [
                    'COLUMN_TYPE' => 'varchar(100)',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => null,
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => 'utf8mb4',
                    'COLLATION_NAME' => 'utf8mb4_general_ci',
                    'COLUMN_COMMENT' => '',
                ],
                'previous' => [
                    'COLUMN_TYPE' => 'varchar(100)',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => null,
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => 'utf8mb4',
                    'COLLATION_NAME' => 'utf8mb4_unicode_ci',
                    'COLUMN_COMMENT' => '',
                ],
                'guard' => 'SELECT 1 AS found FROM `manifesta_nfse_tomadas` WHERE `chave` IS NOT NULL GROUP BY `chave` COLLATE utf8mb4_general_ci HAVING COUNT(*) > 1 LIMIT 1',
            ],
            'nsu' => [
                'sql' => 'varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT \'0\'',
                'expected' => [
                    'COLUMN_TYPE' => 'varchar(20)',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => '0',
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => 'utf8mb4',
                    'COLLATION_NAME' => 'utf8mb4_general_ci',
                    'COLUMN_COMMENT' => '',
                ],
                'previous' => [
                    'COLUMN_TYPE' => 'varchar(20)',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => '0',
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => 'utf8mb4',
                    'COLLATION_NAME' => 'utf8mb4_unicode_ci',
                    'COLUMN_COMMENT' => '',
                ],
            ],
            'situacao' => [
                'sql' => 'varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT \'NORMAL\'',
                'expected' => [
                    'COLUMN_TYPE' => 'varchar(20)',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => 'NORMAL',
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => 'utf8mb4',
                    'COLLATION_NAME' => 'utf8mb4_general_ci',
                    'COLUMN_COMMENT' => '',
                ],
                'previous' => [
                    'COLUMN_TYPE' => 'varchar(20)',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_DEFAULT' => 'NORMAL',
                    'EXTRA' => '',
                    'CHARACTER_SET_NAME' => 'utf8mb4',
                    'COLLATION_NAME' => 'utf8mb4_unicode_ci',
                    'COLUMN_COMMENT' => '',
                ],
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
            [DB::getDatabaseName(), $tableName]
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
            if ($this->columnMatches($current, $definition['expected'])) {
                continue;
            }
            if ($definition['previous'] === null
                || !$this->columnMatches($current, $definition['previous'])) {
                throw new \RuntimeException($tableName . ".{$name}: definição diferente das duas bases comparadas. Nenhuma substituição automática.");
            }

            // Alterações sensíveis exigem valores compatíveis com o tipo/collation da origem.
            if (isset($definition['guard']) && DB::selectOne($definition['guard']) !== null) {
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

    private function ensureTableCollation(string $tableName, string $collation): void
    {
        $current = DB::selectOne(
            'SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [DB::getDatabaseName(), $tableName]
        );
        if ($current !== null && $current->TABLE_COLLATION === $collation) {
            return;
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $collation)) {
            throw new \RuntimeException('Collation inválida.');
        }
        DB::statement('ALTER TABLE `' . $tableName . '` DEFAULT CHARACTER SET utf8mb4 COLLATE ' . $collation);
    }

};
