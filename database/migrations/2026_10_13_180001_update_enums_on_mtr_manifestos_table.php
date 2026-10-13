<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new \RuntimeException('Esta migration requer MySQL/MariaDB.');
        }

        if (!Schema::hasTable('mtr_manifestos')) {
            throw new \RuntimeException('Tabela esperada ausente: mtr_manifestos');
        }

        // Uma migration por tabela: as duas alterações ENUM permanecem juntas.
        $definitions = [
            'tipo_origem' => [
                'type' => "enum('nfe','ticket_pesagem','avulso','recebimento_externo')",
                'values' => ['nfe', 'ticket_pesagem', 'avulso', 'recebimento_externo'],
                'default' => 'avulso',
            ],
            'status' => [
                'type' => "enum('rascunho','transmitido','cancelado','erro','recebido')",
                'values' => ['rascunho', 'transmitido', 'cancelado', 'erro', 'recebido'],
                'default' => 'rascunho',
            ],
        ];

        $columns = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'mtr_manifestos')
            ->whereIn('COLUMN_NAME', array_keys($definitions))
            ->get([
                'COLUMN_NAME', 'COLUMN_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT',
                'COLUMN_COMMENT', 'EXTRA', 'CHARACTER_SET_NAME', 'COLLATION_NAME',
            ])
            ->keyBy('COLUMN_NAME');

        $alterations = [];

        foreach ($definitions as $name => $definition) {
            $column = $columns->get($name);

            if ($column === null) {
                throw new \RuntimeException("Coluna esperada ausente: mtr_manifestos.{$name}");
            }

            if (!preg_match('/^enum\(/i', (string) $column->COLUMN_TYPE)) {
                throw new \RuntimeException("Tipo inesperado em mtr_manifestos.{$name}; esperado ENUM. Nenhuma alteração realizada.");
            }

            if (trim((string) $column->COLUMN_TYPE) === $definition['type']
                && (string) $column->COLUMN_DEFAULT === $definition['default']) {
                continue; // Já corresponde ao tipo e ao DEFAULT solicitados.
            }

            // Não converter silenciosamente dados legados em valores vazios de ENUM.
            if (DB::table('mtr_manifestos')
                ->whereNotNull($name)
                ->whereNotIn($name, $definition['values'])
                ->exists()) {
                throw new \RuntimeException("mtr_manifestos.{$name} contém valores fora do novo ENUM. Nenhuma alteração realizada.");
            }

            if (trim((string) $column->EXTRA) !== '') {
                throw new \RuntimeException("Atributos extras inesperados em mtr_manifestos.{$name}; revisão manual necessária.");
            }

            // Preserva a nulidade, a codificação e o comentário da coluna atual.
            $nullable = strtoupper((string) $column->IS_NULLABLE) === 'YES' ? 'NULL' : 'NOT NULL';
            $charset = (string) $column->CHARACTER_SET_NAME;
            $collation = (string) $column->COLLATION_NAME;

            foreach ([$charset, $collation] as $identifier) {
                if ($identifier !== '' && !preg_match('/^[a-zA-Z0-9_]+$/D', $identifier)) {
                    throw new \RuntimeException("Codificação/collation inesperada em mtr_manifestos.{$name}.");
                }
            }

            $charsetSql = $charset !== '' ? " CHARACTER SET {$charset}" : '';
            $collationSql = $collation !== '' ? " COLLATE {$collation}" : '';
            $comment = (string) $column->COLUMN_COMMENT;
            $commentSql = $comment !== '' ? ' COMMENT ' . DB::getPdo()->quote($comment) : '';

            $alterations[] = "MODIFY COLUMN `{$name}` {$definition['type']}"
                . $charsetSql . $collationSql . " {$nullable} DEFAULT '{$definition['default']}'"
                . $commentSql;
        }

        if ($alterations !== []) {
            // Um único ALTER TABLE mantém as alterações da mesma tabela juntas.
            // Não remove índices, FKs ou registros; o MySQL pode bloquear metadados.
            DB::statement('ALTER TABLE `mtr_manifestos` ' . implode(', ', $alterations));
        }
    }

    public function down(): void
    {
        // Rollback conservador: não reduzir os ENUMs nem excluir valores existentes.
        // A definição anterior varia por instalação e não foi fornecida.
    }
};
