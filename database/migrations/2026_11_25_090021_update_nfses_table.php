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

        if (!Schema::hasTable('nfses')) {
            throw new \RuntimeException('Tabela esperada ausente: nfses');
        }

        // Confere as duas colunas antes do ALTER, inclusive após execução parcial.
        $alterations = [];
        foreach (['serie' => 5, 'codigo_verificacao' => 60] as $columnName => $minimumLength) {
            $definition = $this->expandedVarcharDefinition('nfses', $columnName, $minimumLength);
            if ($definition !== null) {
                $alterations[] = "MODIFY COLUMN `{$columnName}` {$definition}";
            }
        }

        if ($alterations !== []) {
            DB::statement('ALTER TABLE `nfses` ' . implode(', ', $alterations));
        }

        $this->ensureColumn('nfses', 'motivo_rejeicao', function (Blueprint $table) {
            $table->text('motivo_rejeicao')->nullable();
        });
    }

    public function down()
    {
        // Migration somente evolutiva: não reduz os campos nem remove registros.
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

    private function expandedVarcharDefinition(string $tableName, string $columnName, int $minimumLength): ?string
    {
        $current = DB::selectOne(
            'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, CHARACTER_SET_NAME, COLLATION_NAME, COLUMN_COMMENT, EXTRA '
            . 'FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), $tableName, $columnName],
            false
        );

        if ($current === null) {
            throw new \RuntimeException("Coluna esperada ausente: {$tableName}.{$columnName}.");
        }

        if (!preg_match('/^varchar\((\d+)\)$/i', trim((string) $current->COLUMN_TYPE), $match)) {
            throw new \RuntimeException("{$tableName}.{$columnName}: esperado VARCHAR; encontrado {$current->COLUMN_TYPE}. Nenhuma conversão automática.");
        }

        // Não substituir expressões de DEFAULT, colunas geradas ou outros atributos extras.
        if (trim((string) $current->EXTRA) !== '') {
            throw new \RuntimeException("{$tableName}.{$columnName}: atributos extras exigem revisão: {$current->EXTRA}.");
        }

        $currentLength = (int) $match[1];
        $desiredLength = max($currentLength, $minimumLength);
        $nullable = strtoupper((string) $current->IS_NULLABLE) === 'YES';
        $currentDefault = $current->COLUMN_DEFAULT;
        $desiredDefault = $currentDefault;

        // Regra do pacote: código NOT NULL sem default recebe ''.
        // Preserva DEFAULTs existentes e não converte NULL de instalações nullable.
        if ($columnName === 'codigo_verificacao' && !$nullable && $currentDefault === null) {
            $desiredDefault = '';
        }

        if ($currentLength === $desiredLength && $currentDefault === $desiredDefault) {
            return null;
        }

        $charset = (string) $current->CHARACTER_SET_NAME;
        $collation = (string) $current->COLLATION_NAME;
        foreach ([$charset, $collation] as $identifier) {
            if (!preg_match('/^[A-Za-z0-9_]+$/D', $identifier)) {
                throw new \RuntimeException("{$tableName}.{$columnName}: charset/collation inválido ou ausente.");
            }
        }

        $definition = "VARCHAR({$desiredLength}) CHARACTER SET {$charset} COLLATE {$collation}"
            . ($nullable ? ' NULL' : ' NOT NULL');

        if ($desiredDefault !== null) {
            $definition .= ' DEFAULT ' . $this->quoteValue((string) $desiredDefault);
        } elseif ($nullable) {
            $definition .= ' DEFAULT NULL';
        }

        $comment = (string) $current->COLUMN_COMMENT;
        if ($comment !== '') {
            $definition .= ' COMMENT ' . $this->quoteValue($comment);
        }

        return $definition;
    }

    private function quoteValue(string $value): string
    {
        $quoted = DB::connection()->getPdo()->quote($value);
        if ($quoted === false) {
            throw new \RuntimeException('Não foi possível preparar o literal SQL da definição da coluna.');
        }
        return $quoted;
    }
};
