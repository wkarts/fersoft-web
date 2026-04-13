<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MergeMovimentacoesVeiculosPreservingOriginalBase extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('movimentacoes_veiculos')) {
            return;
        }

        Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
            if (!Schema::hasColumn('movimentacoes_veiculos', 'usuario_id')) {
                $table->unsignedInteger('usuario_id')->nullable()->after('filial_id');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'produto_id')) {
                $table->unsignedInteger('produto_id')->nullable()->after('veiculo_id');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'arla_id')) {
                $table->unsignedInteger('arla_id')->nullable()->after('produto_id');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'ajudante_id')) {
                $table->unsignedInteger('ajudante_id')->nullable()->after('motorista_id');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'cliente_id')) {
                $table->unsignedInteger('cliente_id')->nullable()->after('ajudante_id');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'fornecedor_id')) {
                $table->unsignedInteger('fornecedor_id')->nullable()->after('cliente_id');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'data_movimentacao')) {
                $table->date('data_movimentacao')->nullable()->after('tipo_movimentacao_id');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'data_hora_saida')) {
                $table->dateTime('data_hora_saida')->nullable()->after('data_movimentacao');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'data_hora_chegada')) {
                $table->dateTime('data_hora_chegada')->nullable()->after('data_hora_saida');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'km_saida')) {
                $table->integer('km_saida')->nullable()->after('data_hora_chegada');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'km_chegada')) {
                $table->integer('km_chegada')->nullable()->after('km_saida');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'km_inicial')) {
                $table->decimal('km_inicial', 10, 2)->nullable()->after('km_chegada');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'km_final')) {
                $table->decimal('km_final', 10, 2)->nullable()->after('km_inicial');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'custo')) {
                $table->decimal('custo', 10, 2)->nullable()->after('km_final');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'observacoes')) {
                $table->text('observacoes')->nullable()->after('custo');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'observacao')) {
                $table->text('observacao')->nullable()->after('observacoes');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'valor_combustivel')) {
                $table->decimal('valor_combustivel', 10, 2)->default(0.00)->after('status');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'valor_arla')) {
                $table->decimal('valor_arla', 10, 2)->default(0.00)->after('valor_combustivel');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'quantidade_combustivel')) {
                $table->decimal('quantidade_combustivel', 10, 2)->default(0.00)->after('valor_arla');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'quantidade_arla')) {
                $table->decimal('quantidade_arla', 10, 3)->default(0.000)->after('quantidade_combustivel');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'tipo_abastecimento')) {
                $table->enum('tipo_abastecimento', ['interno', 'externo', 'nenhum'])
                    ->default('nenhum')
                    ->after('quantidade_arla');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'destino')) {
                $table->string('destino', 150)->nullable()->after('tipo_abastecimento');
            }

            if (!Schema::hasColumn('movimentacoes_veiculos', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        try {
            DB::statement("
                ALTER TABLE movimentacoes_veiculos
                MODIFY status ENUM('em andamento', 'concluída', 'concluida', 'iniciado', 'finalizado')
                NOT NULL DEFAULT 'em andamento'
            ");
        } catch (\Throwable $e) {
        }

        // índices realmente idempotentes
        $this->addIndexIfNotExists('movimentacoes_veiculos', 'idx_movimentacoes_veiculos_produto_id', ['produto_id']);
        $this->addIndexIfNotExists('movimentacoes_veiculos', 'idx_movimentacoes_veiculos_arla_id', ['arla_id']);
        $this->addIndexIfNotExists('movimentacoes_veiculos', 'idx_movimentacoes_veiculos_ajudante_id', ['ajudante_id']);
        $this->addIndexIfNotExists('movimentacoes_veiculos', 'idx_movimentacoes_veiculos_cliente_id', ['cliente_id']);
        $this->addIndexIfNotExists('movimentacoes_veiculos', 'idx_movimentacoes_veiculos_fornecedor_id', ['fornecedor_id']);

        // foreign keys
        $this->addForeignIfNotExists(
            'movimentacoes_veiculos',
            'movimentacoes_veiculos_empresa_id_foreign',
            'empresa_id',
            'empresas',
            'id',
            'cascade'
        );

        $this->addForeignIfNotExists(
            'movimentacoes_veiculos',
            'movimentacoes_veiculos_filial_id_foreign',
            'filial_id',
            'filials',
            'id',
            'set null'
        );

        $this->addForeignIfNotExists(
            'movimentacoes_veiculos',
            'movimentacoes_veiculos_usuario_id_foreign',
            'usuario_id',
            'usuarios',
            'id',
            'cascade'
        );

        $this->addForeignIfNotExists(
            'movimentacoes_veiculos',
            'movimentacoes_veiculos_veiculo_id_foreign',
            'veiculo_id',
            'veiculos',
            'id',
            'cascade'
        );

        $this->addForeignIfNotExists(
            'movimentacoes_veiculos',
            'movimentacoes_veiculos_motorista_id_foreign',
            'motorista_id',
            'funcionarios',
            'id',
            'set null'
        );

        $this->addForeignIfNotExists(
            'movimentacoes_veiculos',
            'movimentacoes_veiculos_ajudante_id_foreign',
            'ajudante_id',
            'funcionarios',
            'id',
            'set null'
        );

        $this->addForeignIfNotExists(
            'movimentacoes_veiculos',
            'movimentacoes_veiculos_cliente_id_foreign',
            'cliente_id',
            'clientes',
            'id',
            'set null'
        );

        $this->addForeignIfNotExists(
            'movimentacoes_veiculos',
            'movimentacoes_veiculos_fornecedor_id_foreign',
            'fornecedor_id',
            'fornecedors',
            'id',
            'set null'
        );

        $this->addForeignIfNotExists(
            'movimentacoes_veiculos',
            'movimentacoes_veiculos_produto_id_foreign',
            'produto_id',
            'produtos',
            'id',
            'set null'
        );

        $this->addForeignIfNotExists(
            'movimentacoes_veiculos',
            'movimentacoes_veiculos_arla_id_foreign',
            'arla_id',
            'produtos',
            'id',
            'set null'
        );

        $this->addForeignIfNotExists(
            'movimentacoes_veiculos',
            'movimentacoes_veiculos_tipo_movimentacao_id_foreign',
            'tipo_movimentacao_id',
            'tipos_movimentacoes',
            'id',
            'cascade'
        );
    }

    public function down()
    {
        if (!Schema::hasTable('movimentacoes_veiculos')) {
            return;
        }

        // foreign keys criadas por esta migration
        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_tipo_movimentacao_id_foreign');
        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_arla_id_foreign');
        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_produto_id_foreign');
        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_fornecedor_id_foreign');
        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_cliente_id_foreign');
        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_ajudante_id_foreign');
        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_motorista_id_foreign');
        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_veiculo_id_foreign');
        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_usuario_id_foreign');
        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_filial_id_foreign');
        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_empresa_id_foreign');

        // índices criados por esta migration
        $this->dropIndexIfExists('movimentacoes_veiculos', 'idx_movimentacoes_veiculos_fornecedor_id');
        $this->dropIndexIfExists('movimentacoes_veiculos', 'idx_movimentacoes_veiculos_cliente_id');
        $this->dropIndexIfExists('movimentacoes_veiculos', 'idx_movimentacoes_veiculos_ajudante_id');
        $this->dropIndexIfExists('movimentacoes_veiculos', 'idx_movimentacoes_veiculos_arla_id');
        $this->dropIndexIfExists('movimentacoes_veiculos', 'idx_movimentacoes_veiculos_produto_id');

        // remove apenas as colunas adicionadas por esta mesclagem
        Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('movimentacoes_veiculos', 'deleted_at')) {
                $columnsToDrop[] = 'deleted_at';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'destino')) {
                $columnsToDrop[] = 'destino';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'tipo_abastecimento')) {
                $columnsToDrop[] = 'tipo_abastecimento';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'quantidade_arla')) {
                $columnsToDrop[] = 'quantidade_arla';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'quantidade_combustivel')) {
                $columnsToDrop[] = 'quantidade_combustivel';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'valor_arla')) {
                $columnsToDrop[] = 'valor_arla';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'valor_combustivel')) {
                $columnsToDrop[] = 'valor_combustivel';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'observacao')) {
                $columnsToDrop[] = 'observacao';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'observacoes')) {
                $columnsToDrop[] = 'observacoes';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'custo')) {
                $columnsToDrop[] = 'custo';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'km_final')) {
                $columnsToDrop[] = 'km_final';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'km_inicial')) {
                $columnsToDrop[] = 'km_inicial';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'km_chegada')) {
                $columnsToDrop[] = 'km_chegada';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'km_saida')) {
                $columnsToDrop[] = 'km_saida';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'data_hora_chegada')) {
                $columnsToDrop[] = 'data_hora_chegada';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'data_hora_saida')) {
                $columnsToDrop[] = 'data_hora_saida';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'data_movimentacao')) {
                $columnsToDrop[] = 'data_movimentacao';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'fornecedor_id')) {
                $columnsToDrop[] = 'fornecedor_id';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'cliente_id')) {
                $columnsToDrop[] = 'cliente_id';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'ajudante_id')) {
                $columnsToDrop[] = 'ajudante_id';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'arla_id')) {
                $columnsToDrop[] = 'arla_id';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'produto_id')) {
                $columnsToDrop[] = 'produto_id';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'usuario_id')) {
                $columnsToDrop[] = 'usuario_id';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // status não é revertido aqui para evitar perda/inconsistência de dados
        // em ambientes que já estejam utilizando os valores adicionais.
    }

    private function addIndexIfNotExists(string $table, string $indexName, array $columns): void
    {
        $database = DB::getDatabaseName();

        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();

        if (!$exists) {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
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

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $database = DB::getDatabaseName();

        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();

        if ($exists) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }

    private function dropForeignIfExists(string $table, string $foreignName): void
    {
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
}
