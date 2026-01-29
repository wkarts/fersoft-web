<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tbl = 'ANP';

        if (!Schema::hasTable($tbl)) {
            Schema::create($tbl, function (Blueprint $table) {
                // No seu Delphi o ID estava comentado — mantendo CODIGO como chave de negócio.
                $table->integer('CODIGO');
                $table->primary('CODIGO');

                $table->string('DESCRICAO', 250)->nullable();
                $table->decimal('ADREMICMS', 15, 4)->nullable();
                $table->string('MONOFASICO', 1)->nullable();
                $table->integer('PBIO')->default(0)->notNull();
                $table->integer('ORIGCOMB')->default(0)->notNull()->comment(
                    'O Grupo "origComb" deve ser preenchido na NFe quando a operação for com biodiesel/óleo diesel B, etanol/gasolina (misturas) ou GLP/GLGN contendo GLGNn ou GLGNi.'
                );
                $table->string('UTRIB', 10)->nullable();

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

        $this->ensureUnique($tbl, 'UQ_ANP_CODIGO', ['CODIGO']);
        $this->ensureIndex($tbl,  'IDX_ANP_DESCRICAO', ['DESCRICAO']);
    }

    public function down(): void
    {
        Schema::dropIfExists('ANP');
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
