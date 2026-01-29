<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tbl = 'CNAE_ITEM_LISTA_SERVICOS';

        if (!Schema::hasTable($tbl)) {
            Schema::create($tbl, function (Blueprint $table) {
                $table->bigIncrements('ID');

                $table->integer('CNAE')->notNull();
                $table->string('DESCRICAO_CNAE', 500)->nullable();
                $table->integer('COD_SERVICO')->notNull();
                $table->string('DESCRICAO_SERVICO', 500)->nullable();
                $table->integer('COD_TRIB_MUNICIPIO')->nullable();
                $table->decimal('ALIQUOTA', 15, 2)->nullable();
                $table->string('PERMITE_TRIB_FORA', 1)->nullable();
                $table->string('RETENCAO_OBRIGATORIA', 1)->nullable();
                $table->string('PERMITE_REDUCAO_BC', 1)->nullable();
                $table->decimal('DEDUCAO_MAX', 15, 2)->nullable();

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

        $this->ensureUnique($tbl, 'UQ_CNAE_ITEM_LISTA', ['CNAE', 'COD_SERVICO']);

        $this->ensureIndex($tbl, 'IDX_CNAE_ITEM_LISTA_CNAE',     ['CNAE']);
        $this->ensureIndex($tbl, 'IDX_CNAE_ITEM_LISTA_CODSERV',  ['COD_SERVICO']);
        $this->ensureIndex($tbl, 'IDX_CNAE_ITEM_LISTA_CODMUN',   ['COD_TRIB_MUNICIPIO']);
        $this->ensureIndex($tbl, 'IDX_CNAE_ITEM_LISTA_ALIQUOTA', ['ALIQUOTA']);
    }

    public function down(): void
    {
        Schema::dropIfExists('CNAE_ITEM_LISTA_SERVICOS');
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
