<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tbl = 'tbtipi_import';

        // 1) CreateTableIfNotExists (idempotente)
        if (!Schema::hasTable($tbl)) {
            Schema::create($tbl, function (Blueprint $table) {
                $table->bigIncrements('id');

                // colunas da planilha
                $table->string('ncm', 20)->nullable();
                $table->string('ex', 10)->nullable();
                $table->string('descricao', 3000)->nullable(); // no seu Delphi: 3000 (depois complementa 2000)
                $table->string('aliquota_raw', 20)->nullable();
                $table->decimal('aliquota_perc', 15, 4)->nullable();
                $table->string('cst_ibs_cbs', 20)->nullable();
                $table->string('cclasstrib', 30)->nullable();
                $table->string('tipo_reducao', 120)->nullable();
                $table->string('lc214_codigo_raw', 30)->nullable();

                // padrão eloquent (no seu padrão de nomes)
                $table->uuid('eloquent_uuid')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->dateTime('deleted_at')->nullable();
            });
        }

        // 2) Se a base já tinha tabela antiga sem ID: tenta injetar ID e PK (best-effort)
        //    (em Laravel isso pode variar por SGBD; aqui é conservador e não derruba a migration)
        if (Schema::hasTable($tbl) && !Schema::hasColumn($tbl, 'id')) {
            $this->tryAddAutoIdAndPk($tbl);
        }

        // 3) Garante campos padrão (idempotente)
        $this->ensureColumn($tbl, 'eloquent_uuid', fn(Blueprint $t) => $t->uuid('eloquent_uuid')->nullable());
        $this->ensureColumn($tbl, 'created_at',    fn(Blueprint $t) => $t->dateTime('created_at')->nullable());
        $this->ensureColumn($tbl, 'updated_at',    fn(Blueprint $t) => $t->dateTime('updated_at')->nullable());
        $this->ensureColumn($tbl, 'deleted_at',    fn(Blueprint $t) => $t->dateTime('deleted_at')->nullable());

        // 4) Complementa colunas caso a tabela já existisse (idempotente)
        // OBS: no seu Delphi algumas eram "NotNull" na complementação; aqui mantenho conservador como nullable,
        //      porque mudar NULL -> NOT NULL em base antiga pode quebrar.
        $this->ensureColumn($tbl, 'ncm',              fn(Blueprint $t) => $t->string('ncm', 20)->nullable());
        $this->ensureColumn($tbl, 'ex',               fn(Blueprint $t) => $t->string('ex', 10)->nullable());
        $this->ensureColumn($tbl, 'descricao',        fn(Blueprint $t) => $t->string('descricao', 3000)->nullable());
        $this->ensureColumn($tbl, 'aliquota_raw',     fn(Blueprint $t) => $t->string('aliquota_raw', 20)->nullable());
        $this->ensureColumn($tbl, 'aliquota_perc',    fn(Blueprint $t) => $t->decimal('aliquota_perc', 15, 4)->nullable());
        $this->ensureColumn($tbl, 'cst_ibs_cbs',      fn(Blueprint $t) => $t->string('cst_ibs_cbs', 20)->nullable());
        $this->ensureColumn($tbl, 'cclasstrib',       fn(Blueprint $t) => $t->string('cclasstrib', 30)->nullable());
        $this->ensureColumn($tbl, 'tipo_reducao',     fn(Blueprint $t) => $t->string('tipo_reducao', 120)->nullable());
        $this->ensureColumn($tbl, 'lc214_codigo_raw', fn(Blueprint $t) => $t->string('lc214_codigo_raw', 30)->nullable());

        // 5) Índices / chaves de negócio
        $this->ensureUnique($tbl, 'uq_tbtipi_import_key', ['ncm','ex','lc214_codigo_raw','cclasstrib']);
        $this->ensureIndex($tbl,  'ix_tbtipi_import_ncm',  ['ncm']);
        $this->ensureIndex($tbl,  'ix_tbtipi_import_lc214',['lc214_codigo_raw']);

        // 6) Triggers padrão (UUID + CREATED_AT + UPDATED_AT) — MySQL/Postgres
        $this->ensureTriggers($tbl);
    }

    public function down(): void
    {
        // Opcional: se você quiser também dropar triggers, dá pra fazer aqui.
        Schema::dropIfExists('tbtipi_import');
    }

    // -----------------------
    // Helpers idempotentes
    // -----------------------

    private function ensureColumn(string $table, string $col, \Closure $adder): void
    {
        if (!Schema::hasTable($table)) return;
        if (Schema::hasColumn($table, $col)) return;

        try {
            Schema::table($table, function (Blueprint $t) use ($adder) {
                $adder($t);
            });
        } catch (\Throwable $e) {
            // idempotente: ignora falhas (col já existe / lock / etc.)
        }
    }

    private function ensureIndex(string $table, string $indexName, array $columns): void
    {
        if (!$this->tableExists($table)) return;
        if ($this->indexExists($table, $indexName)) return;

        try {
            Schema::table($table, fn (Blueprint $t) => $t->index($columns, $indexName));
        } catch (\Throwable $e) {
            // idempotente: ignora se já existir/colidir
        }
    }

    private function ensureUnique(string $table, string $indexName, array $columns): void
    {
        if (!$this->tableExists($table)) return;
        if ($this->indexExists($table, $indexName)) return;

        try {
            Schema::table($table, fn (Blueprint $t) => $t->unique($columns, $indexName));
        } catch (\Throwable $e) {
            // idempotente: ignora se já existir/colidir
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
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

    private function tryAddAutoIdAndPk(string $table): void
    {
        $driver = DB::getDriverName();

        // Em bases antigas, pode existir um PK composto/colunas diferentes.
        // Aqui é best-effort e NÃO derruba a migration.
        try {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                // adiciona ID auto increment e tenta criar PK
                DB::statement("ALTER TABLE `$table` ADD COLUMN `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT FIRST");
                // tenta setar PK
                DB::statement("ALTER TABLE `$table` ADD PRIMARY KEY (`ID`)");
                return;
            }

            if ($driver === 'pgsql') {
                // cria coluna ID identity e tenta pk
                DB::statement("ALTER TABLE \"$table\" ADD COLUMN \"ID\" BIGINT GENERATED BY DEFAULT AS IDENTITY");
                DB::statement("ALTER TABLE \"$table\" ADD PRIMARY KEY (\"ID\")");
                return;
            }

            if ($driver === 'sqlite') {
                // SQLite não permite bem esse tipo de alteração de PK sem rebuild da tabela.
                // Mantém conservador: não faz nada.
                return;
            }
        } catch (\Throwable $e) {
            // idempotente: ignora falhas
        }
    }

    private function ensureTriggers(string $table): void
    {
        $driver = DB::getDriverName();

        try {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                // MySQL/MariaDB: cria triggers (drop+create para ser idempotente)
                $bi = $table . '_BI0';
                $bu = $table . '_BU0';

                DB::unprepared("DROP TRIGGER IF EXISTS `$bi`;");
                DB::unprepared("
                    CREATE TRIGGER `$bi` BEFORE INSERT ON `$table`
                    FOR EACH ROW
                    BEGIN
                        IF NEW.ELOQUENT_UUID IS NULL THEN
                            SET NEW.ELOQUENT_UUID = UUID();
                        END IF;

                        IF NEW.CREATED_AT IS NULL THEN
                            SET NEW.CREATED_AT = CURRENT_TIMESTAMP;
                        END IF;
                    END
                ");

                DB::unprepared("DROP TRIGGER IF EXISTS `$bu`;");
                DB::unprepared("
                    CREATE TRIGGER `$bu` BEFORE UPDATE ON `$table`
                    FOR EACH ROW
                    BEGIN
                        IF NEW.ELOQUENT_UUID IS NULL THEN
                            SET NEW.ELOQUENT_UUID = UUID();
                        END IF;

                        SET NEW.UPDATED_AT = CURRENT_TIMESTAMP;
                    END
                ");
                return;
            }

            if ($driver === 'pgsql') {
                // Postgres: função + triggers (idempotente)
                // Nota: gen_random_uuid() requer pgcrypto. Se você não tiver, troque por uuid_generate_v4() + extensão uuid-ossp.
                DB::statement("CREATE EXTENSION IF NOT EXISTS pgcrypto;");

                $fn_bi = 'fn_tbtipi_import_bi0';
                $fn_bu = 'fn_tbtipi_import_bu0';
                $tr_bi = 'trg_' . strtolower($table) . '_bi0';
                $tr_bu = 'trg_' . strtolower($table) . '_bu0';

                DB::unprepared("
                    CREATE OR REPLACE FUNCTION {$fn_bi}() RETURNS trigger AS $$
                    BEGIN
                        IF NEW.\"ELOQUENT_UUID\" IS NULL THEN
                            NEW.\"ELOQUENT_UUID\" := gen_random_uuid();
                        END IF;

                        IF NEW.\"CREATED_AT\" IS NULL THEN
                            NEW.\"CREATED_AT\" := CURRENT_TIMESTAMP;
                        END IF;

                        RETURN NEW;
                    END;
                    $$ LANGUAGE plpgsql;
                ");

                DB::unprepared("
                    CREATE OR REPLACE FUNCTION {$fn_bu}() RETURNS trigger AS $$
                    BEGIN
                        IF NEW.\"ELOQUENT_UUID\" IS NULL THEN
                            NEW.\"ELOQUENT_UUID\" := gen_random_uuid();
                        END IF;

                        NEW.\"UPDATED_AT\" := CURRENT_TIMESTAMP;
                        RETURN NEW;
                    END;
                    $$ LANGUAGE plpgsql;
                ");

                DB::unprepared("DROP TRIGGER IF EXISTS {$tr_bi} ON \"{$table}\";");
                DB::unprepared("
                    CREATE TRIGGER {$tr_bi}
                    BEFORE INSERT ON \"{$table}\"
                    FOR EACH ROW
                    EXECUTE FUNCTION {$fn_bi}();
                ");

                DB::unprepared("DROP TRIGGER IF EXISTS {$tr_bu} ON \"{$table}\";");
                DB::unprepared("
                    CREATE TRIGGER {$tr_bu}
                    BEFORE UPDATE ON \"{$table}\"
                    FOR EACH ROW
                    EXECUTE FUNCTION {$fn_bu}();
                ");
                return;
            }

            // SQLite: ignore (sem triggers)
        } catch (\Throwable $e) {
            // idempotente: ignora falhas de trigger/perm/driver
        }
    }
};
