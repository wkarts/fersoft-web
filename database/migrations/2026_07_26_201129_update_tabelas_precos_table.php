<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tabelas_precos')) {
            return;
        }

        $this->ensureNullableAndComment('tabelas_precos', 'tipo_ajuste', true, 'acrescimo, desconto ou valor_fixo');
    }

    public function down()
    {
        // Migration exclusivamente evolutiva.
        return;
    }


    private function ensureNullableAndComment(
        string $tableName,
        string $columnName,
        bool $nullable,
        ?string $comment = null
    ): void {
        if (
            !$this->isMysql()
            || !Schema::hasTable($tableName)
            || !Schema::hasColumn($tableName, $columnName)
        ) {
            return;
        }

        $column = $this->columnInfo($tableName, $columnName);

        if (!$column) {
            return;
        }

        $currentNullable = strtoupper((string) $column->is_nullable) === 'YES';
        $currentComment = (string) ($column->column_comment ?? '');
        $desiredComment = $comment === null ? $currentComment : $comment;

        if ($currentNullable === $nullable && $currentComment === $desiredComment) {
            return;
        }

        /*
         * Uma coluna com relacionamento ativo pode ser bloqueada pelo MySQL
         * mesmo quando somente a nulabilidade ou o comentário é alterado.
         * Como esta migration é estritamente não destrutiva, ela registra
         * a pendência e preserva o relacionamento existente.
         */
        if ($this->columnParticipatesInForeignKey($tableName, $columnName)) {
            $this->warnForwardOnly(
                "Alteração pendente em {$tableName}.{$columnName}: a coluna participa de uma chave estrangeira ativa."
            );
            return;
        }

        if (!$nullable && $this->columnHasNullValues($tableName, $columnName)) {
            $this->warnForwardOnly(
                "Alteração ignorada em {$tableName}.{$columnName}: existem valores nulos."
            );
            return;
        }

        $definition = $this->renderCurrentColumnDefinition(
            $column,
            $nullable,
            $desiredComment
        );

        if ($definition === null) {
            $this->warnForwardOnly(
                "Alteração ignorada em {$tableName}.{$columnName}: definição atual não suportada com segurança."
            );
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE %s MODIFY COLUMN %s %s',
            $this->quoteIdentifier($tableName),
            $this->quoteIdentifier($columnName),
            $definition
        ));
    }

    private function columnInfo(string $tableName, string $columnName): ?object
    {
        if (!$this->isMysql()) {
            return null;
        }

        return DB::table('information_schema.COLUMNS')
            ->select([
                'COLUMN_TYPE as column_type',
                'DATA_TYPE as data_type',
                'IS_NULLABLE as is_nullable',
                'COLUMN_DEFAULT as column_default',
                'EXTRA as extra',
                'COLUMN_COMMENT as column_comment',
                'CHARACTER_SET_NAME as character_set_name',
                'COLLATION_NAME as collation_name',
                'GENERATION_EXPRESSION as generation_expression',
            ])
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->first();
    }

    private function renderCurrentColumnDefinition(
        object $column,
        bool $nullable,
        string $comment
    ): ?string {
        $generationExpression = trim((string) ($column->generation_expression ?? ''));

        if ($generationExpression !== '') {
            return null;
        }

        $definition = strtoupper((string) $column->column_type);

        $characterSet = trim((string) ($column->character_set_name ?? ''));
        $collation = trim((string) ($column->collation_name ?? ''));

        if ($characterSet !== '' && preg_match('/^[A-Za-z0-9_]+$/', $characterSet)) {
            $definition .= ' CHARACTER SET ' . $characterSet;
        }

        if ($collation !== '' && preg_match('/^[A-Za-z0-9_]+$/', $collation)) {
            $definition .= ' COLLATE ' . $collation;
        }

        $definition .= $nullable ? ' NULL' : ' NOT NULL';
        $definition .= $this->renderDefaultClause($column, $nullable);

        $extra = strtolower(trim((string) ($column->extra ?? '')));

        if (str_contains($extra, 'on update current_timestamp')) {
            $definition .= ' ON UPDATE CURRENT_TIMESTAMP';
        }

        if (str_contains($extra, 'auto_increment')) {
            $definition .= ' AUTO_INCREMENT';
        }

        if (str_contains($extra, 'invisible')) {
            $definition .= ' INVISIBLE';
        }

        $definition .= ' COMMENT ' . DB::getPdo()->quote($comment);

        return $definition;
    }

    private function renderDefaultClause(object $column, bool $nullable): string
    {
        $default = $column->column_default ?? null;
        $dataType = strtolower((string) ($column->data_type ?? ''));

        if ($default === null) {
            return $nullable ? ' DEFAULT NULL' : '';
        }

        $defaultString = (string) $default;
        $normalized = strtoupper(trim($defaultString));

        if (
            preg_match('/^CURRENT_TIMESTAMP(?:\(\d+\))?$/i', $defaultString)
            || preg_match('/^CURRENT_DATE(?:\(\))?$/i', $defaultString)
            || preg_match('/^CURRENT_TIME(?:\(\d+\))?$/i', $defaultString)
            || preg_match('/^\(.+\)$/s', trim($defaultString))
        ) {
            return ' DEFAULT ' . $defaultString;
        }

        $numericTypes = [
            'tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint',
            'decimal', 'numeric', 'float', 'double', 'real', 'bit',
        ];

        if (in_array($dataType, $numericTypes, true) && is_numeric($defaultString)) {
            return ' DEFAULT ' . $defaultString;
        }

        if ($normalized === 'NULL') {
            return ' DEFAULT NULL';
        }

        return ' DEFAULT ' . DB::getPdo()->quote($defaultString);
    }

    private function columnParticipatesInForeignKey(
        string $tableName,
        string $columnName
    ): bool {
        if (!$this->isMysql()) {
            return false;
        }

        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }

    private function columnHasNullValues(
        string $tableName,
        string $columnName
    ): bool {
        return DB::table($tableName)
            ->whereNull($columnName)
            ->limit(1)
            ->exists();
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
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
