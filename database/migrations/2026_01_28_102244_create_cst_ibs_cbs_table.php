<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tbl = 'CST_IBS_CBS';

        if (!Schema::hasTable($tbl)) {
            Schema::create($tbl, function (Blueprint $table) {
                $table->bigIncrements('ID');

                // legado / negócio
                $table->integer('ID_CST_IBS_CBS')->notNull();
                $table->string('CST_IBS_CBS', 8)->nullable();
                $table->string('DESCRICAO_CST_IBS_CBS', 255)->nullable();

                $table->string('IND_GIBSCBS', 1)->nullable();
                $table->string('IND_GIBSCBSMONO', 1)->nullable();
                $table->string('IND_GRED', 1)->nullable();
                $table->string('IND_GDIF', 1)->nullable();
                $table->string('IND_GTRANSFCRED', 1)->nullable();
                $table->string('INDNFE', 1)->nullable();
                $table->string('INDNFCE', 1)->nullable();
                $table->string('INDCTE', 1)->nullable();
                $table->string('INDCTEOS', 1)->nullable();
                $table->string('INDBPE', 1)->nullable();
                $table->string('INDBPETM', 1)->nullable();
                $table->string('INDNF3E', 1)->nullable();
                $table->string('INDNFCOM', 1)->nullable();
                $table->string('INDNFSE', 1)->nullable();

                // padrão Eloquent
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

        $this->ensureUnique($tbl, 'UQ_CST_IBS_CBS_LEGACY', ['ID_CST_IBS_CBS']);
        $this->ensureIndex($tbl,  'IDX_CST_IBS_CBS_CST',   ['CST_IBS_CBS']);
    }

    public function down(): void
    {
        Schema::dropIfExists('CST_IBS_CBS');
    }

    private function ensureIndex(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) return;
        try { Schema::table($table, fn (Blueprint $t) => $t->index($columns, $indexName)); } catch (\Throwable $e) {}
    }

    private function ensureUnique(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) return;
        try { Schema::table($table, fn (Blueprint $t) => $t->unique($columns, $indexName)); } catch (\Throwable $e) {}
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
