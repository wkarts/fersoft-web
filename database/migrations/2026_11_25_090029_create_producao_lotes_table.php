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

        if (!Schema::hasTable('producao_lotes')) {
            Schema::create('producao_lotes', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->unsignedBigInteger('id')->autoIncrement();
                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('filial_id')->nullable();
                $table->unsignedInteger('usuario_id');
                $table->string('codigo_lote', 40);
                $table->unsignedBigInteger('pesagem_entrada_id')->nullable();
                $table->unsignedInteger('fornecedor_id')->nullable();
                $table->unsignedInteger('cliente_origem_id')->nullable();
                $table->string('cnpj_origem', 18)->nullable()->comment('14 digitos limpos da filial/matriz geradora');
                $table->string('cnpj_raiz', 8)->nullable()->comment('8 primeiros digitos para agrupar Matriz + Filiais');
                $table->string('nfe_entrada', 50)->nullable();
                $table->string('mtr_entrada', 50)->nullable();
                $table->string('foto_chegada', 255)->nullable();
                $table->unsignedInteger('produto_origem_id')->nullable();
                $table->decimal('peso_entrada', 18, 4)->default('0.0000');
                $table->decimal('peso_produzido', 18, 4)->default('0.0000');
                $table->decimal('peso_perda', 18, 4)->default('0.0000');
                $table->unsignedInteger('nfe_perda_id')->nullable();
                $table->unsignedInteger('nfe_perda_numero')->nullable();
                $table->string('nfe_perda_chave', 44)->nullable();
                $table->enum('nfe_perda_status', ['nao_aplicavel', 'pendente', 'emitida'])->default('nao_aplicavel');
                $table->enum('status', ['novo', 'em_corte', 'transformado'])->default('novo');
                $table->text('observacoes')->nullable();
                $table->timestamp('concluido_em')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            });
            DB::statement('ALTER TABLE `producao_lotes` ROW_FORMAT = DYNAMIC');
        }

        $this->ensureTableCollation('producao_lotes', 'utf8mb4_unicode_ci');

        $this->addIndexIfMissing('producao_lotes', 'uk_producao_lotes_codigo', ['empresa_id', 'codigo_lote'], true);

        $this->addIndexIfMissing('producao_lotes', 'idx_producao_lotes_empresa_status', ['empresa_id', 'status'], false);

        $this->addIndexIfMissing('producao_lotes', 'idx_producao_lotes_cnpj_raiz', ['empresa_id', 'cnpj_raiz'], false);

        $this->addIndexIfMissing('producao_lotes', 'idx_producao_lotes_cnpj_origem', ['empresa_id', 'cnpj_origem'], false);
    }

    public function down()
    {
        // Migration somente evolutiva.
        return;
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

    private function addIndexIfMissing(string $tableName, string $indexName, array $columns, bool $unique = false): void
    {
        $rows = DB::select(
            'SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE, SUB_PART, COLLATION, INDEX_TYPE '
            . 'FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY INDEX_NAME, SEQ_IN_INDEX',
            [DB::getDatabaseName(), $tableName]
        );
        $indexes = [];
        foreach ($rows as $row) {
            $indexes[$row->INDEX_NAME]['columns'][] = $row->COLUMN_NAME;
            $indexes[$row->INDEX_NAME]['unique'] = (int) $row->NON_UNIQUE === 0;
            $indexes[$row->INDEX_NAME]['simple'] = ($indexes[$row->INDEX_NAME]['simple'] ?? true)
                && $row->SUB_PART === null && $row->COLLATION === 'A' && strtoupper($row->INDEX_TYPE) === 'BTREE';
        }
        $expected = ['columns' => $columns, 'unique' => $unique, 'simple' => true];
        if (isset($indexes[$indexName])) {
            if ($indexes[$indexName] !== $expected) {
                throw new \RuntimeException($tableName . ": índice {$indexName} existente com outra definição; preservado.");
            }
            return;
        }
        foreach ($indexes as $existingName => $index) {
            if ($index === $expected && ($indexName !== 'PRIMARY' || $existingName === 'PRIMARY')) {
                return; // Índice equivalente já presente, mesmo com outro nome.
            }
        }
        $quoted = implode(', ', array_map(static fn ($column) => "`{$column}`", $columns));
        $notNull = implode(' AND ', array_map(static fn ($column) => "`{$column}` IS NOT NULL", $columns));
        if ($unique && DB::selectOne('SELECT 1 AS found FROM `' . $tableName . '` WHERE ' . $notNull
            . ' GROUP BY ' . $quoted . ' HAVING COUNT(*) > 1 LIMIT 1') !== null) {
            throw new \RuntimeException($tableName . ": duplicidades impedem o índice {$indexName}; nenhum registro foi alterado.");
        }
        if ($indexName === 'PRIMARY') {
            $nulls = implode(' OR ', array_map(static fn ($column) => "`{$column}` IS NULL", $columns));
            if (DB::selectOne('SELECT 1 AS found FROM `' . $tableName . '` WHERE ' . $nulls . ' LIMIT 1') !== null) {
                throw new \RuntimeException($tableName . ': valores nulos impedem a chave primária.');
            }
        }
        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName, $unique) {
            if ($unique) {
                $table->unique($columns, $indexName);
            } else {
                $table->index($columns, $indexName);
            }
        });
    }

};
