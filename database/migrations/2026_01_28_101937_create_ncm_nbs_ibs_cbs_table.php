<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tbl = 'NCM_NBS_IBS_CBS';

        if (!Schema::hasTable($tbl)) {
            Schema::create($tbl, function (Blueprint $table) {
                $table->bigIncrements('ID');

                // Legado / negócio
                $table->integer('ID_NCM_NBS_IBS_CBS')->notNull();
                $table->integer('ID_CCLASS_IBS_CBS')->nullable();
                $table->string('CST_IBS_CBS', 8)->nullable();
                $table->string('CCLASS_TRIB', 8)->nullable();
                $table->string('NCM_NBS_IBS_CBS', 9)->nullable();
                $table->string('NOME_NCM_NBS_IBS_CBS', 2000)->nullable();
                $table->string('TIPO_NCM_NBS_IBS_CBS', 3)->nullable();
                $table->date('INICIO_VIGENCIA')->nullable();
                $table->date('TERMININO_VIGENCIA')->nullable();

                // Padrão Eloquent (no seu padrão de nomes)
                $table->uuid('ELOQUENT_UUID')->nullable();
                $table->dateTime('CREATED_AT')->nullable();
                $table->dateTime('UPDATED_AT')->nullable();
                $table->dateTime('DELETED_AT')->nullable();
            });
        } else {
            // Garante colunas padrão se faltarem (idempotente/conservador)
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                if (!Schema::hasColumn($tbl, 'ELOQUENT_UUID')) $table->uuid('ELOQUENT_UUID')->nullable();
                if (!Schema::hasColumn($tbl, 'CREATED_AT'))    $table->dateTime('CREATED_AT')->nullable();
                if (!Schema::hasColumn($tbl, 'UPDATED_AT'))    $table->dateTime('UPDATED_AT')->nullable();
                if (!Schema::hasColumn($tbl, 'DELETED_AT'))    $table->dateTime('DELETED_AT')->nullable();
            });
        }

        $this->ensureUnique($tbl, 'UQ_NCM_NBS_IBS_CBS_LEGACY', ['ID_NCM_NBS_IBS_CBS']);
        $this->ensureIndex($tbl,  'IDX_NCM_NBS_IBS_CBS_COD',   ['NCM_NBS_IBS_CBS']);
        $this->ensureIndex($tbl,  'IDX_NCM_NBS_IBS_CBS_VIG',   ['INICIO_VIGENCIA', 'TERMININO_VIGENCIA']);
    }

    public function down(): void
    {
        Schema::dropIfExists('NCM_NBS_IBS_CBS');
    }

    private function ensureIndex(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) return;

        try {
            Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
                $t->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // idempotente: ignora se já existir/colidir
        }
    }

    private function ensureUnique(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) return;

        try {
            Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
                $t->unique($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // idempotente: ignora se já existir/colidir
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        try {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $db = DB::getDatabaseName();
                $r = DB::selectOne(
                    "SELECT 1 AS ok
                       FROM information_schema.statistics
                      WHERE table_schema = ?
                        AND table_name = ?
                        AND index_name = ?
                      LIMIT 1",
                    [$db, $table, $indexName]
                );
                return (bool) $r;
            }

            if ($driver === 'pgsql') {
                $r = DB::selectOne(
                    "SELECT 1 AS ok
                       FROM pg_indexes
                      WHERE (tablename = ? OR tablename = lower(?))
                        AND indexname = ?
                      LIMIT 1",
                    [$table, $table, $indexName]
                );
                return (bool) $r;
            }

            if ($driver === 'sqlite') {
                $rows = DB::select("PRAGMA index_list('$table')");
                foreach ($rows as $row) {
                    if (($row->name ?? null) === $indexName) return true;
                }
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }
};
