<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tbl = 'cnae_item_lista_servicos';

        if (!Schema::hasTable($tbl)) {
            Schema::create($tbl, function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->integer('cnae')->notNull();
                $table->string('descricao_cnae', 500)->nullable();
                $table->integer('cod_servico')->notNull();
                $table->string('descricao_servico', 500)->nullable();
                $table->integer('cod_trib_municipio')->nullable();
                $table->decimal('aliquota', 15, 2)->nullable();
                $table->string('permite_trib_fora', 1)->nullable();
                $table->string('retencao_obrigatoria', 1)->nullable();
                $table->string('permite_reducao_bc', 1)->nullable();
                $table->decimal('deducao_max', 15, 2)->nullable();

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

        $this->ensureUnique($tbl, 'uq_cnae_item_lista', ['cnae', 'cod_servico']);

        $this->ensureIndex($tbl, 'idx_cnae_item_lista_cnae',     ['cnae']);
        $this->ensureIndex($tbl, 'idx_cnae_item_lista_codserv',  ['cod_servico']);
        $this->ensureIndex($tbl, 'idx_cnae_item_lista_codmun',   ['cod_trib_municipio']);
        $this->ensureIndex($tbl, 'idx_cnae_item_lista_aliquota', ['aliquota']);
    }

    public function down(): void
    {
        Schema::dropIfExists('cnae_item_lista_servicos');
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
