<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tbl = 'ncm_nbs_ibs_cbs';

        if (!Schema::hasTable($tbl)) {
            Schema::create($tbl, function (Blueprint $table) {
                $table->bigIncrements('id');

                // Legado / negócio
                $table->integer('id_ncm_nbs_ibs_cbs')->notNull();
                $table->integer('id_cclass_ibs_cbs')->nullable();
                $table->string('cst_ibs_cbs', 8)->nullable();
                $table->string('cclass_trib', 8)->nullable();
                $table->string('ncm_nbs_ibs_cbs', 9)->nullable();
                $table->string('nome_ncm_nbs_ibs_cbs', 2000)->nullable();
                $table->string('tipo_ncm_nbs_ibs_cbs', 3)->nullable();
                $table->date('inicio_vigencia')->nullable();
                $table->date('terminino_vigencia')->nullable();

                // Padrão Eloquent (no seu padrão de nomes)
                $table->uuid('eloquent_uuid')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->dateTime('deleted_at')->nullable();
            });
        } else {
            // Garante colunas padrão se faltarem (idempotente/conservador)
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                if (!Schema::hasColumn($tbl, 'eloquent_uuid')) $table->uuid('eloquent_uuid')->nullable();
                if (!Schema::hasColumn($tbl, 'created_at'))    $table->dateTime('created_at')->nullable();
                if (!Schema::hasColumn($tbl, 'updated_at'))    $table->dateTime('updated_at')->nullable();
                if (!Schema::hasColumn($tbl, 'deleted_at'))    $table->dateTime('deleted_at')->nullable();
            });
        }

        $this->ensureUnique($tbl, 'uq_ncm_nbs_ibs_cbs_legacy', ['id_ncm_nbs_ibs_cbs']);
        $this->ensureIndex($tbl,  'idx_ncm_nbs_ibs_cbs_cod',   ['ncm_nbs_ibs_cbs']);
        $this->ensureIndex($tbl,  'idx_ncm_nbs_ibs_cbs_vig',   ['inicio_vigencia', 'terminino_vigencia']);
    }

    public function down(): void
    {
        Schema::dropIfExists('ncm_nbs_ibs_cbs');
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
