<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tbl = 'class_trib_ibs_cbs';

        if (!Schema::hasTable($tbl)) {
            Schema::create($tbl, function (Blueprint $table) {
                $table->bigIncrements('id');

                // Legado / negócio
                $table->integer('id_cclas_ibs_cbs')->notNull();
                $table->integer('id_cst_ibs_cbs')->nullable();
                $table->string('cst_ibs_cbs', 8)->nullable();
                $table->string('descricao_cst_ibs_cbs', 257)->nullable();
                $table->string('cclasstrib', 8)->nullable();
                $table->string('nome_cclasstrib', 257)->nullable();
                $table->string('descricao_cclasstrib', 257)->nullable();
                $table->text('lc_redacao')->nullable();
                $table->string('lc_214_25', 257)->nullable();
                $table->string('tipo_de_aliquota', 257)->nullable();

                $table->decimal('predibs', 18, 4)->default(0)->notNull();
                $table->decimal('predcbs', 18, 4)->default(0)->notNull();
                $table->decimal('pred_ibs_cbs', 18, 4)->default(0)->notNull();

                $table->string('ind_redutorbc', 1)->nullable();
                $table->string('ind_gtribregular', 1)->nullable();
                $table->string('ind_credpres', 1)->nullable();
                $table->string('indmono', 1)->nullable();
                $table->string('indmonoreten', 1)->nullable();
                $table->string('indmonoret', 1)->nullable();
                $table->string('indmonodif', 1)->nullable();

                $table->string('credito_para', 257)->nullable();
                $table->date('dinivig')->nullable();
                $table->date('dfimvig')->nullable();
                $table->dateTime('dataatualizacao')->nullable();

                // Padrão Eloquent
                $table->uuid('eloquent_uuid')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->dateTime('deleted_at')->nullable();
            });
        } else {
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                if (!Schema::hasColumn($tbl, 'eloquent_uuid')) $table->uuid('eloquent_uuid')->nullable();
                if (!Schema::hasColumn($tbl, 'created_at'))    $table->dateTime('created_at')->nullable();
                if (!Schema::hasColumn($tbl, 'updated_at'))    $table->dateTime('updated_at')->nullable();
                if (!Schema::hasColumn($tbl, 'deleted_at'))    $table->dateTime('deleted_at')->nullable();
            });
        }

        $this->ensureUnique($tbl, 'uq_class_trib_ibs_cbs_legacy', ['id_cclas_ibs_cbs']);
        $this->ensureIndex($tbl,  'idx_class_trib_cst',           ['cst_ibs_cbs']);
        $this->ensureIndex($tbl,  'idx_class_trib_cclas',         ['cclasstrib']);
        $this->ensureIndex($tbl,  'idx_class_trib_vig',           ['dinivig', 'dfimvig']);
    }

    public function down(): void
    {
        Schema::dropIfExists('class_trib_ibs_cbs');
    }

    private function ensureIndex(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) return;
        try {
            Schema::table($table, fn (Blueprint $t) => $t->index($columns, $indexName));
        } catch (\Throwable $e) {}
    }

    private function ensureUnique(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) return;
        try {
            Schema::table($table, fn (Blueprint $t) => $t->unique($columns, $indexName));
        } catch (\Throwable $e) {}
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        try {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $db = DB::getDatabaseName();
                return (bool) DB::selectOne(
                    "SELECT 1 FROM information_schema.statistics
                      WHERE table_schema=? AND table_name=? AND index_name=? LIMIT 1",
                    [$db, $table, $indexName]
                );
            }
            if ($driver === 'pgsql') {
                return (bool) DB::selectOne(
                    "SELECT 1 FROM pg_indexes
                      WHERE (tablename=? OR tablename=lower(?)) AND indexname=? LIMIT 1",
                    [$table, $table, $indexName]
                );
            }
            if ($driver === 'sqlite') {
                $rows = DB::select("PRAGMA index_list('$table')");
                foreach ($rows as $row) if (($row->name ?? null) === $indexName) return true;
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }
};
