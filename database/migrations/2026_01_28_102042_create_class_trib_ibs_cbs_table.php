<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tbl = 'CLASS_TRIB_IBS_CBS';

        if (!Schema::hasTable($tbl)) {
            Schema::create($tbl, function (Blueprint $table) {
                $table->bigIncrements('ID');

                // Legado / negócio
                $table->integer('ID_CCLAS_IBS_CBS')->notNull();
                $table->integer('ID_CST_IBS_CBS')->nullable();
                $table->string('CST_IBS_CBS', 8)->nullable();
                $table->string('DESCRICAO_CST_IBS_CBS', 257)->nullable();
                $table->string('CCLASSTRIB', 8)->nullable();
                $table->string('NOME_CCLASSTRIB', 257)->nullable();
                $table->string('DESCRICAO_CCLASSTRIB', 257)->nullable();
                $table->text('LC_REDACAO')->nullable();
                $table->string('LC_214_25', 257)->nullable();
                $table->string('TIPO_DE_ALIQUOTA', 257)->nullable();

                $table->decimal('PREDIBS', 18, 4)->default(0)->notNull();
                $table->decimal('PREDCBS', 18, 4)->default(0)->notNull();
                $table->decimal('PRED_IBS_CBS', 18, 4)->default(0)->notNull();

                $table->string('IND_REDUTORBC', 1)->nullable();
                $table->string('IND_GTRIBREGULAR', 1)->nullable();
                $table->string('IND_CREDPRES', 1)->nullable();
                $table->string('INDMONO', 1)->nullable();
                $table->string('INDMONORETEN', 1)->nullable();
                $table->string('INDMONORET', 1)->nullable();
                $table->string('INDMONODIF', 1)->nullable();

                $table->string('CREDITO_PARA', 257)->nullable();
                $table->date('DINIVIG')->nullable();
                $table->date('DFIMVIG')->nullable();
                $table->dateTime('DATAATUALIZACAO')->nullable();

                // Padrão Eloquent
                $table->uuid('ELOQUENT_UUID')->nullable();
                $table->dateTime('CREATED_AT')->nullable();
                $table->dateTime('UPDATED_AT')->nullable();
                $table->dateTime('DELETED_AT')->nullable();
            });
        } else {
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                if (!Schema::hasColumn($tbl, 'ELOQUENT_UUID')) $table->uuid('ELOQUENT_UUID')->nullable();
                if (!Schema::hasColumn($tbl, 'CREATED_AT'))    $table->dateTime('CREATED_AT')->nullable();
                if (!Schema::hasColumn($tbl, 'UPDATED_AT'))    $table->dateTime('UPDATED_AT')->nullable();
                if (!Schema::hasColumn($tbl, 'DELETED_AT'))    $table->dateTime('DELETED_AT')->nullable();
            });
        }

        $this->ensureUnique($tbl, 'UQ_CLASS_TRIB_IBS_CBS_LEGACY', ['ID_CCLAS_IBS_CBS']);
        $this->ensureIndex($tbl,  'IDX_CLASS_TRIB_CST',           ['CST_IBS_CBS']);
        $this->ensureIndex($tbl,  'IDX_CLASS_TRIB_CCLAS',         ['CCLASSTRIB']);
        $this->ensureIndex($tbl,  'IDX_CLASS_TRIB_VIG',           ['DINIVIG', 'DFIMVIG']);
    }

    public function down(): void
    {
        Schema::dropIfExists('CLASS_TRIB_IBS_CBS');
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
