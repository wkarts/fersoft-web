<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MergeMovimentacoesVeiculosTableWithoutDataLoss extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('movimentacoes_veiculos')) {
            return;
        }

        Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
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
        });

        Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
            if (!Schema::hasColumn('movimentacoes_veiculos', 'km_inicial')) {
                if (Schema::hasColumn('movimentacoes_veiculos', 'km_chegada')) {
                    $table->decimal('km_inicial', 10, 2)->nullable()->after('km_chegada');
                } elseif (Schema::hasColumn('movimentacoes_veiculos', 'fornecedor_id')) {
                    $table->decimal('km_inicial', 10, 2)->nullable()->after('fornecedor_id');
                } else {
                    $table->decimal('km_inicial', 10, 2)->nullable();
                }
            }
        });

        Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
            if (!Schema::hasColumn('movimentacoes_veiculos', 'km_final')) {
                if (Schema::hasColumn('movimentacoes_veiculos', 'km_inicial')) {
                    $table->decimal('km_final', 10, 2)->nullable()->after('km_inicial');
                } elseif (Schema::hasColumn('movimentacoes_veiculos', 'km_chegada')) {
                    $table->decimal('km_final', 10, 2)->nullable()->after('km_chegada');
                } elseif (Schema::hasColumn('movimentacoes_veiculos', 'fornecedor_id')) {
                    $table->decimal('km_final', 10, 2)->nullable()->after('fornecedor_id');
                } else {
                    $table->decimal('km_final', 10, 2)->nullable();
                }
            }
        });

        Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
            if (!Schema::hasColumn('movimentacoes_veiculos', 'data_hora_saida')) {
                if (Schema::hasColumn('movimentacoes_veiculos', 'data_movimentacao')) {
                    $table->dateTime('data_hora_saida')->nullable()->after('data_movimentacao');
                } elseif (Schema::hasColumn('movimentacoes_veiculos', 'tipo_movimentacao_id')) {
                    $table->dateTime('data_hora_saida')->nullable()->after('tipo_movimentacao_id');
                } else {
                    $table->dateTime('data_hora_saida')->nullable();
                }
            }
        });

        Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
            if (!Schema::hasColumn('movimentacoes_veiculos', 'data_hora_chegada')) {
                if (Schema::hasColumn('movimentacoes_veiculos', 'data_hora_saida')) {
                    $table->dateTime('data_hora_chegada')->nullable()->after('data_hora_saida');
                } elseif (Schema::hasColumn('movimentacoes_veiculos', 'data_movimentacao')) {
                    $table->dateTime('data_hora_chegada')->nullable()->after('data_movimentacao');
                } else {
                    $table->dateTime('data_hora_chegada')->nullable();
                }
            }
        });

        Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
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

            if (!Schema::hasColumn('movimentacoes_veiculos', 'observacao')) {
                if (Schema::hasColumn('movimentacoes_veiculos', 'observacoes')) {
                    $table->text('observacao')->nullable()->after('observacoes');
                } else {
                    $table->text('observacao')->nullable();
                }
            }
        });

        try {
            DB::statement("
                ALTER TABLE movimentacoes_veiculos
                MODIFY status ENUM('em andamento', 'concluída', 'concluida', 'iniciado', 'finalizado')
                NOT NULL DEFAULT 'em andamento'
            ");
        } catch (\Throwable $e) {
            // evita quebra em ambientes onde o enum já tenha sido ajustado manualmente
        }

        Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
            try {
                $table->index('produto_id', 'idx_movimentacoes_veiculos_produto_id');
            } catch (\Throwable $e) {
            }

            try {
                $table->index('arla_id', 'idx_movimentacoes_veiculos_arla_id');
            } catch (\Throwable $e) {
            }

            try {
                $table->index('ajudante_id', 'idx_movimentacoes_veiculos_ajudante_id');
            } catch (\Throwable $e) {
            }

            try {
                $table->index('cliente_id', 'idx_movimentacoes_veiculos_cliente_id');
            } catch (\Throwable $e) {
            }

            try {
                $table->index('fornecedor_id', 'idx_movimentacoes_veiculos_fornecedor_id');
            } catch (\Throwable $e) {
            }
        });

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
    }

    public function down()
    {
        if (!Schema::hasTable('movimentacoes_veiculos')) {
            return;
        }

        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_fornecedor_id_foreign');
        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_cliente_id_foreign');
        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_ajudante_id_foreign');
        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_arla_id_foreign');
        $this->dropForeignIfExists('movimentacoes_veiculos', 'movimentacoes_veiculos_produto_id_foreign');

        $this->dropIndexIfExists('movimentacoes_veiculos', 'idx_movimentacoes_veiculos_fornecedor_id');
        $this->dropIndexIfExists('movimentacoes_veiculos', 'idx_movimentacoes_veiculos_cliente_id');
        $this->dropIndexIfExists('movimentacoes_veiculos', 'idx_movimentacoes_veiculos_ajudante_id');
        $this->dropIndexIfExists('movimentacoes_veiculos', 'idx_movimentacoes_veiculos_arla_id');
        $this->dropIndexIfExists('movimentacoes_veiculos', 'idx_movimentacoes_veiculos_produto_id');

        Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('movimentacoes_veiculos', 'produto_id')) {
                $columnsToDrop[] = 'produto_id';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'arla_id')) {
                $columnsToDrop[] = 'arla_id';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'ajudante_id')) {
                $columnsToDrop[] = 'ajudante_id';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'cliente_id')) {
                $columnsToDrop[] = 'cliente_id';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'fornecedor_id')) {
                $columnsToDrop[] = 'fornecedor_id';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'km_inicial')) {
                $columnsToDrop[] = 'km_inicial';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'km_final')) {
                $columnsToDrop[] = 'km_final';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'data_hora_saida')) {
                $columnsToDrop[] = 'data_hora_saida';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'data_hora_chegada')) {
                $columnsToDrop[] = 'data_hora_chegada';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'valor_combustivel')) {
                $columnsToDrop[] = 'valor_combustivel';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'valor_arla')) {
                $columnsToDrop[] = 'valor_arla';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'quantidade_combustivel')) {
                $columnsToDrop[] = 'quantidade_combustivel';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'quantidade_arla')) {
                $columnsToDrop[] = 'quantidade_arla';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'tipo_abastecimento')) {
                $columnsToDrop[] = 'tipo_abastecimento';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'destino')) {
                $columnsToDrop[] = 'destino';
            }

            if (Schema::hasColumn('movimentacoes_veiculos', 'observacao')) {
                $columnsToDrop[] = 'observacao';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        try {
            DB::statement("
                ALTER TABLE movimentacoes_veiculos
                MODIFY status ENUM('em andamento', 'concluída')
                NOT NULL DEFAULT 'em andamento'
            ");
        } catch (\Throwable $e) {
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
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        $database = DB::getDatabaseName();

        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if (!$exists && Schema::hasColumn($table, $column)) {
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
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
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

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

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

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

}
