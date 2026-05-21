<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->scopeAdpDevices();
        $this->scopeAdpCameras();
    }

    public function down(): void
    {
        if (Schema::hasTable('adp_devices')) {
            $this->dropIndexIfExists('adp_devices', 'adp_devices_empresa_config_uuid_unique');
        }

        if (Schema::hasTable('adp_cameras')) {
            $this->dropIndexIfExists('adp_cameras', 'adp_cameras_empresa_config_uuid_unique');
        }
    }

    private function scopeAdpDevices(): void
    {
        if (!Schema::hasTable('adp_devices')) {
            return;
        }

        $this->dropIndexIfExists('adp_devices', 'adp_devices_empresa_uuid_unique');

        if (!$this->indexExists('adp_devices', 'adp_devices_empresa_config_uuid_unique')) {
            $this->createUniqueIndex('adp_devices', 'adp_devices_empresa_config_uuid_unique', ['empresa_id', 'integrador_config_id', 'device_uuid']);
        }
    }

    private function scopeAdpCameras(): void
    {
        if (!Schema::hasTable('adp_cameras')) {
            return;
        }

        $this->dropIndexIfExists('adp_cameras', 'adp_cameras_empresa_uuid_unique');

        if (!$this->indexExists('adp_cameras', 'adp_cameras_empresa_config_uuid_unique')) {
            $this->createUniqueIndex('adp_cameras', 'adp_cameras_empresa_config_uuid_unique', ['empresa_id', 'integrador_config_id', 'camera_uuid']);
        }
    }

    private function createUniqueIndex(string $table, string $index, array $columns): void
    {
        $driver = DB::getDriverName();
        $columnSql = implode(', ', array_map(fn ($column) => $this->wrap($column), $columns));

        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX ' . $this->wrap($index) . ' ON ' . $this->wrap($table) . ' (' . $columnSql . ')');
            return;
        }

        DB::statement('CREATE UNIQUE INDEX ' . $this->wrap($index) . ' ON ' . $this->wrap($table) . ' (' . $columnSql . ')');
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (!$this->indexExists($table, $index)) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('DROP INDEX ' . $this->wrap($index) . ' ON ' . $this->wrap($table));
            return;
        }

        DB::statement('DROP INDEX ' . $this->wrap($index));
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $database = DB::getDatabaseName();
            return DB::table('information_schema.statistics')
                ->where('table_schema', $database)
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->exists();
        }

        if ($driver === 'sqlite') {
            $indexes = DB::select('PRAGMA index_list(' . $this->wrap($table) . ')');
            foreach ($indexes as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }
            return false;
        }

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('tablename', $table)
                ->where('indexname', $index)
                ->exists();
        }

        return false;
    }

    private function wrap(string $identifier): string
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return '`' . str_replace('`', '``', $identifier) . '`';
        }

        return '"' . str_replace('"', '""', $identifier) . '"';
    }
};
