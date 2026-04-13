<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateManutencaoItensTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('manutencao_itens')) {
            return;
        }

        $manutencoesPk = $this->getPrimaryKeyColumn('manutencoes');
        $manutencoesPkMeta = $manutencoesPk ? $this->getColumnMeta('manutencoes', $manutencoesPk) : null;

        $produtosPk = $this->getPrimaryKeyColumn('produtos');
        $produtosPkMeta = $produtosPk ? $this->getColumnMeta('produtos', $produtosPk) : null;

        Schema::create('manutencao_itens', function (Blueprint $table) use (
            $manutencoesPkMeta,
            $produtosPkMeta
        ) {
            $table->increments('id');

            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('usuario_id');
            $table->unsignedInteger('filial_id');

            // manutencao_id com tipo compatível com a PK real da tabela manutencoes
            $this->addCompatibleColumn($table, 'manutencao_id', $manutencoesPkMeta, false);

            // produto_id com tipo compatível com a PK real da tabela produtos
            $this->addCompatibleColumn($table, 'produto_id', $produtosPkMeta, true);

            $table->string('descricao', 255);
            $table->decimal('quantidade', 10, 2)->default(1.00);
            $table->decimal('valor_unitario', 10, 2)->default(0.00);
            $table->decimal('subtotal', 10, 2)->default(0.00);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('empresa_id', 'idx_manutencao_itens_empresa_id');
            $table->index('usuario_id', 'idx_manutencao_itens_usuario_id');
            $table->index('filial_id', 'idx_manutencao_itens_filial_id');
            $table->index('manutencao_id', 'manutencao_itens_manutencao_id_foreign');
            $table->index('produto_id', 'idx_manutencao_itens_produto_id');
        });

        // FKs padrão
        $this->addForeignIfNotExists(
            'manutencao_itens',
            'manutencao_itens_empresa_id_foreign',
            'empresa_id',
            'empresas',
            'id',
            'cascade'
        );

        $this->addForeignIfNotExists(
            'manutencao_itens',
            'manutencao_itens_usuario_id_foreign',
            'usuario_id',
            'usuarios',
            'id',
            'cascade'
        );

        $this->addForeignIfNotExists(
            'manutencao_itens',
            'manutencao_itens_filial_id_foreign',
            'filial_id',
            'filials',
            'id',
            'cascade'
        );

        // FK para manutencoes usando a PK real encontrada
        if ($manutencoesPk) {
            $this->addForeignIfNotExists(
                'manutencao_itens',
                'manutencao_itens_manutencao_fk',
                'manutencao_id',
                'manutencoes',
                $manutencoesPk,
                'cascade'
            );
        }

        // FK para produtos usando a PK real encontrada
        if ($produtosPk) {
            $this->addForeignIfNotExists(
                'manutencao_itens',
                'manutencao_itens_produto_fk',
                'produto_id',
                'produtos',
                $produtosPk,
                'set null'
            );
        }
    }

    public function down()
    {
        if (!Schema::hasTable('manutencao_itens')) {
            return;
        }

        $this->dropForeignIfExists('manutencao_itens', 'manutencao_itens_produto_fk');
        $this->dropForeignIfExists('manutencao_itens', 'manutencao_itens_manutencao_fk');
        $this->dropForeignIfExists('manutencao_itens', 'manutencao_itens_filial_id_foreign');
        $this->dropForeignIfExists('manutencao_itens', 'manutencao_itens_usuario_id_foreign');
        $this->dropForeignIfExists('manutencao_itens', 'manutencao_itens_empresa_id_foreign');

        Schema::dropIfExists('manutencao_itens');
    }

    private function getPrimaryKeyColumn(string $table): ?string
    {
        if ($this->isSqlite()) {
            return 'id';
        }

        $database = DB::getDatabaseName();

        $pk = DB::table('information_schema.KEY_COLUMN_USAGE as kcu')
            ->join('information_schema.TABLE_CONSTRAINTS as tc', function ($join) {
                $join->on('tc.CONSTRAINT_NAME', '=', 'kcu.CONSTRAINT_NAME')
                    ->on('tc.TABLE_SCHEMA', '=', 'kcu.TABLE_SCHEMA')
                    ->on('tc.TABLE_NAME', '=', 'kcu.TABLE_NAME');
            })
            ->where('tc.CONSTRAINT_TYPE', 'PRIMARY KEY')
            ->where('kcu.TABLE_SCHEMA', $database)
            ->where('kcu.TABLE_NAME', $table)
            ->orderBy('kcu.ORDINAL_POSITION')
            ->value('kcu.COLUMN_NAME');

        return $pk ?: null;
    }

    private function getColumnMeta(string $table, string $column): ?object
    {
        if ($this->isSqlite()) {
            return (object) [
                'COLUMN_NAME' => $column,
                'DATA_TYPE' => 'integer',
                'COLUMN_TYPE' => 'integer',
                'IS_NULLABLE' => 'YES',
            ];
        }

        $database = DB::getDatabaseName();

        return DB::table('information_schema.COLUMNS')
            ->select('COLUMN_NAME', 'DATA_TYPE', 'COLUMN_TYPE', 'IS_NULLABLE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->first();
    }

    private function addCompatibleColumn(Blueprint $table, string $columnName, ?object $meta, bool $nullable = false): void
    {
        // fallback seguro
        if (!$meta) {
            $col = $table->unsignedInteger($columnName);
            if ($nullable) {
                $col->nullable();
            }
            return;
        }

        $dataType = strtolower($meta->DATA_TYPE ?? '');
        $columnType = strtolower($meta->COLUMN_TYPE ?? '');
        $unsigned = str_contains($columnType, 'unsigned');

        if ($dataType === 'bigint') {
            $col = $unsigned
                ? $table->unsignedBigInteger($columnName)
                : $table->bigInteger($columnName);
        } elseif ($dataType === 'int' || $dataType === 'integer') {
            $col = $unsigned
                ? $table->unsignedInteger($columnName)
                : $table->integer($columnName);
        } elseif ($dataType === 'smallint') {
            $col = $unsigned
                ? $table->unsignedSmallInteger($columnName)
                : $table->smallInteger($columnName);
        } elseif ($dataType === 'tinyint') {
            $col = $unsigned
                ? $table->unsignedTinyInteger($columnName)
                : $table->tinyInteger($columnName);
        } else {
            // fallback
            $col = $table->unsignedInteger($columnName);
        }

        if ($nullable) {
            $col->nullable();
        }
    }

    private function addForeignIfNotExists(
        string $table,
        string $foreignName,
        string $column,
        string $referencesTable,
        string $referencesColumn = 'id',
        string $onDelete = 'cascade'
    ): void {
        if ($this->isSqlite()) {
            return;
        }

        $database = DB::getDatabaseName();

        if (!Schema::hasColumn($table, $column) || !Schema::hasColumn($referencesTable, $referencesColumn)) {
            return;
        }

        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if (!$exists) {
            Schema::table($table, function (Blueprint $table) use (
                $foreignName,
                $column,
                $referencesTable,
                $referencesColumn,
                $onDelete
            ) {
                $table->foreign($column, $foreignName)
                    ->references($referencesColumn)
                    ->on($referencesTable)
                    ->onDelete($onDelete);
            });
        }
    }

    private function dropForeignIfExists(string $table, string $foreignName): void
    {
        if ($this->isSqlite()) {
            return;
        }

        $database = DB::getDatabaseName();

        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists) {
            Schema::table($table, function (Blueprint $table) use ($foreignName) {
                $table->dropForeign($foreignName);
            });
        }
    }

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

}
