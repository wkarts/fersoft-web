<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected string $table = 'categoria_contas';

    public function up(): void
    {
        $this->ensureMergeAuditTable();

        $this->table = $this->resolveTableName();

        if (!Schema::hasTable($this->table)) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | COLUNAS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasColumn($this->table, 'filial_id')) {
            if ($this->isSqlite()) {
                Schema::table($this->table, function (Blueprint $table) {
                    $table->integer('filial_id')->nullable();
                });
            } else {
                DB::statement("ALTER TABLE {$this->table} ADD COLUMN filial_id INT UNSIGNED NULL AFTER empresa_id");
            }

            $this->markCreated('column', "{$this->table}.filial_id");
        }

        if (!Schema::hasColumn($this->table, 'usuario_id')) {
            if ($this->isSqlite()) {
                Schema::table($this->table, function (Blueprint $table) {
                    $table->integer('usuario_id')->nullable();
                });
            } else {
                DB::statement("ALTER TABLE {$this->table} ADD COLUMN usuario_id INT UNSIGNED NULL AFTER filial_id");
            }

            $this->markCreated('column', "{$this->table}.usuario_id");
        }

        /*
        |--------------------------------------------------------------------------
        | MODIFY TYPES
        |--------------------------------------------------------------------------
        */
        if (!$this->isSqlite()) {
            try {
                DB::statement("ALTER TABLE {$this->table} MODIFY empresa_id INT UNSIGNED NOT NULL");
            } catch (\Throwable $e) {}

            try {
                DB::statement("ALTER TABLE {$this->table} MODIFY filial_id INT UNSIGNED NULL");
            } catch (\Throwable $e) {}

            try {
                DB::statement("ALTER TABLE {$this->table} MODIFY usuario_id INT UNSIGNED NULL");
            } catch (\Throwable $e) {}
        }

        /*
        |--------------------------------------------------------------------------
        | ÍNDICES
        |--------------------------------------------------------------------------
        */
        if ($this->addIndexIfNotExists($this->table, "idx_{$this->table}_empresa_id", ['empresa_id'])) {
            $this->markCreated('index', "{$this->table}.idx_{$this->table}_empresa_id");
        }

        if ($this->addIndexIfNotExists($this->table, "idx_{$this->table}_filial_id", ['filial_id'])) {
            $this->markCreated('index', "{$this->table}.idx_{$this->table}_filial_id");
        }

        if ($this->addIndexIfNotExists($this->table, "idx_{$this->table}_usuario_id", ['usuario_id'])) {
            $this->markCreated('index', "{$this->table}.idx_{$this->table}_usuario_id");
        }

        /*
        |--------------------------------------------------------------------------
        | FOREIGN KEYS
        |--------------------------------------------------------------------------
        */
        if ($this->addForeignIfNotExists(
            $this->table,
            "{$this->table}_empresa_id_foreign",
            'empresa_id',
            'empresas',
            'id',
            'cascade',
            'cascade'
        )) {
            $this->markCreated('foreign', "{$this->table}.{$this->table}_empresa_id_foreign");
        }

        if ($this->addForeignIfNotExists(
            $this->table,
            "{$this->table}_filial_id_foreign",
            'filial_id',
            'filials',
            'id',
            'set null',
            'cascade'
        )) {
            $this->markCreated('foreign', "{$this->table}.{$this->table}_filial_id_foreign");
        }

        if ($this->addForeignIfNotExists(
            $this->table,
            "{$this->table}_usuario_id_foreign",
            'usuario_id',
            'usuarios',
            'id',
            'set null',
            'cascade'
        )) {
            $this->markCreated('foreign', "{$this->table}.{$this->table}_usuario_id_foreign");
        }
    }

    public function down(): void
    {
        $this->ensureMergeAuditTable();

        $this->table = $this->resolveTableName();

        if (!Schema::hasTable($this->table)) {
            return;
        }

        if ($this->wasCreated('foreign', "{$this->table}.{$this->table}_usuario_id_foreign")) {
            $this->dropForeignIfExists($this->table, "{$this->table}_usuario_id_foreign");
            $this->forgetCreated('foreign', "{$this->table}.{$this->table}_usuario_id_foreign");
        }

        if ($this->wasCreated('foreign', "{$this->table}.{$this->table}_filial_id_foreign")) {
            $this->dropForeignIfExists($this->table, "{$this->table}_filial_id_foreign");
            $this->forgetCreated('foreign', "{$this->table}.{$this->table}_filial_id_foreign");
        }

        if ($this->wasCreated('foreign', "{$this->table}.{$this->table}_empresa_id_foreign")) {
            $this->dropForeignIfExists($this->table, "{$this->table}_empresa_id_foreign");
            $this->forgetCreated('foreign', "{$this->table}.{$this->table}_empresa_id_foreign");
        }

        if ($this->wasCreated('index', "{$this->table}.idx_{$this->table}_usuario_id")) {
            $this->dropIndexIfExists($this->table, "idx_{$this->table}_usuario_id");
            $this->forgetCreated('index', "{$this->table}.idx_{$this->table}_usuario_id");
        }

        if ($this->wasCreated('index', "{$this->table}.idx_{$this->table}_filial_id")) {
            $this->dropIndexIfExists($this->table, "idx_{$this->table}_filial_id");
            $this->forgetCreated('index', "{$this->table}.idx_{$this->table}_filial_id");
        }

        if ($this->wasCreated('index', "{$this->table}.idx_{$this->table}_empresa_id")) {
            $this->dropIndexIfExists($this->table, "idx_{$this->table}_empresa_id");
            $this->forgetCreated('index', "{$this->table}.idx_{$this->table}_empresa_id");
        }

        if (!$this->isSqlite()) {
            if ($this->wasCreated('column', "{$this->table}.usuario_id") && Schema::hasColumn($this->table, 'usuario_id')) {
                DB::statement("ALTER TABLE {$this->table} DROP COLUMN usuario_id");
                $this->forgetCreated('column', "{$this->table}.usuario_id");
            }

            if ($this->wasCreated('column', "{$this->table}.filial_id") && Schema::hasColumn($this->table, 'filial_id')) {
                DB::statement("ALTER TABLE {$this->table} DROP COLUMN filial_id");
                $this->forgetCreated('column', "{$this->table}.filial_id");
            }
        }
    }

    private function resolveTableName(): string
    {
        if (Schema::hasTable('categoria_contas')) {
            return 'categoria_contas';
        }

        if (Schema::hasTable('categorias_contas')) {
            return 'categorias_contas';
        }

        return 'categoria_contas';
    }

    private function ensureMergeAuditTable(): void
    {
        if (!Schema::hasTable('migration_merge_audit')) {
            Schema::create('migration_merge_audit', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('migration_name', 191);
                $table->string('object_type', 50);
                $table->string('object_name', 191);
                $table->timestamps();
                $table->unique(['migration_name', 'object_type', 'object_name'], 'uq_migration_merge_audit');
            });
        }
    }

    private function markCreated(string $type, string $name): void
    {
        DB::table('migration_merge_audit')->updateOrInsert(
            [
                'migration_name' => static::class,
                'object_type' => $type,
                'object_name' => $name,
            ],
            [
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function wasCreated(string $type, string $name): bool
    {
        return DB::table('migration_merge_audit')
            ->where('migration_name', static::class)
            ->where('object_type', $type)
            ->where('object_name', $name)
            ->exists();
    }

    private function forgetCreated(string $type, string $name): void
    {
        DB::table('migration_merge_audit')
            ->where('migration_name', static::class)
            ->where('object_type', $type)
            ->where('object_name', $name)
            ->delete();
    }

    private function addIndexIfNotExists(string $table, string $indexName, array $columns, bool $unique = false): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        if ($this->isSqlite()) {
            return false;
        }

        $database = DB::getDatabaseName();

        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();

        if ($exists) {
            return false;
        }

        Schema::table($table, function (Blueprint $table) use ($columns, $indexName, $unique) {
            if ($unique) {
                $table->unique($columns, $indexName);
            } else {
                $table->index($columns, $indexName);
            }
        });

        return true;
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if ($this->isSqlite()) {
            return;
        }

        $database = DB::getDatabaseName();

        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();

        if ($exists) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }

    private function addForeignIfNotExists(
        string $table,
        string $constraintName,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $onDelete = 'restrict',
        string $onUpdate = 'cascade'
    ): bool {
        if (!Schema::hasTable($table) || $this->isSqlite()) {
            return false;
        }

        $database = DB::getDatabaseName();

        $exists = DB::table('information_schema.referential_constraints')
            ->where('constraint_schema', $database)
            ->where('table_name', $table)
            ->where('constraint_name', $constraintName)
            ->exists();

        if ($exists) {
            return false;
        }

        DB::statement("
            ALTER TABLE {$table}
            ADD CONSTRAINT {$constraintName}
            FOREIGN KEY ({$column})
            REFERENCES {$referencedTable} ({$referencedColumn})
            ON DELETE {$onDelete}
            ON UPDATE {$onUpdate}
        ");

        return true;
    }

    private function dropForeignIfExists(string $table, string $constraintName): void
    {
        if (!Schema::hasTable($table) || $this->isSqlite()) {
            return;
        }

        $database = DB::getDatabaseName();

        $exists = DB::table('information_schema.referential_constraints')
            ->where('constraint_schema', $database)
            ->where('table_name', $table)
            ->where('constraint_name', $constraintName)
            ->exists();

        if ($exists) {
            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$constraintName}");
        }
    }

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }
};
