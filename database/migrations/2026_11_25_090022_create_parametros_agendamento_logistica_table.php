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

        if (!Schema::hasTable('parametros_agendamento_logistica')) {
            Schema::create('parametros_agendamento_logistica', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->integer('id')->autoIncrement();
                $table->integer('empresa_id');
                $table->integer('vagas_manha')->nullable()->default('3')->comment('Qtd veículos disponíveis de manhã');
                $table->time('horario_manha_inicio')->nullable()->default('08:00:00');
                $table->time('horario_manha_fim')->nullable()->default('12:00:00');
                $table->integer('vagas_tarde')->nullable()->default('3')->comment('Qtd veículos disponíveis à tarde');
                $table->time('horario_tarde_inicio')->nullable()->default('13:30:00');
                $table->time('horario_tarde_fim')->nullable()->default('17:30:00');
                $table->decimal('tempo_franquia_horas', 5, 2)->nullable()->default('4.00')->comment('Tempo limite que o veículo pode ficar no cliente');
                $table->decimal('valor_taxa_hora_extra', 10, 2)->nullable()->default('150.00')->comment('Valor cobrado se ultrapassar a franquia');
                $table->string('dias_atendimento', 50)->nullable()->default('1,2,3,4,5')->comment('0=Dom, 1=Seg ... 6=Sab');
                $table->integer('antecedencia_minima_horas')->nullable()->default('2');
                $table->string('tipos_veiculos_disponiveis', 255)->nullable()->default('Caminhão Roll-on/Roll-off,Caminhão Poliguindaste (Brooks),Caminhão Munck,Caminhão Baú / Carga Seca');
                $table->integer('categoria_residuos_id')->nullable();
                $table->integer('categoria_cacambas_id')->nullable();
                $table->text('observacoes_regras')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->boolean('notificar_atraso_whatsapp')->nullable()->default('1');
                $table->integer('minutos_tolerancia_atraso')->nullable()->default('20');
                $table->integer('funcionario_gestor_frota_id')->nullable();
            });
            DB::statement('ALTER TABLE `parametros_agendamento_logistica` ROW_FORMAT = DYNAMIC');
        }

        $this->ensureTableCollation('parametros_agendamento_logistica', 'utf8mb4_unicode_ci');

        $this->addIndexIfMissing('parametros_agendamento_logistica', 'uk_empresa_param_log', ['empresa_id'], true);
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
