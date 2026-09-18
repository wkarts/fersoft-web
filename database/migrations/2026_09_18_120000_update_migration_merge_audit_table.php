<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // O marcador é criado no MESMO DDL da coluna: não depende de memória nem de uma segunda tabela.
    private const OWNER = 'fersoft:2026_09_18_120000_update_migration_merge_audit_table:metadata_json';

    public function up()
    {
        $this->withAuditLock(function () {
            if (!Schema::hasTable('migration_merge_audit')) {
                throw new \RuntimeException('migration_merge_audit deve existir pelas migrations históricas. Não execute este pacote isoladamente em um banco sem os pré-requisitos.');
            }
            foreach (['id', 'migration_name', 'object_type', 'object_name', 'created_at', 'updated_at'] as $column) {
                if (!Schema::hasColumn('migration_merge_audit', $column)) {
                    throw new \RuntimeException('Estrutura de auditoria preexistente incompleta: ' . $column);
                }
            }
            $columns = DB::select(
                'SELECT INDEX_NAME AS name, NON_UNIQUE AS non_unique, SEQ_IN_INDEX AS seq, COLUMN_NAME AS column_name, SUB_PART AS prefix_len
                 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY INDEX_NAME, SEQ_IN_INDEX',
                [DB::getDatabaseName(), 'migration_merge_audit']
            );
            $indexes = [];
            foreach ($columns as $column) {
                if ((int) $column->non_unique === 0 && $column->prefix_len === null) {
                    $indexes[$column->name][] = $column->column_name;
                }
            }
            if (!in_array(['migration_name', 'object_type', 'object_name'], $indexes, true)) {
                throw new \RuntimeException('A auditoria exige a chave única de migration_name/object_type/object_name já usada pelo projeto.');
            }
            $existing = $this->metadataColumn();
            if ($existing !== null) {
                if ($existing->data_type !== 'json' || $existing->is_nullable !== 'YES' || $existing->column_default !== null) {
                    throw new \RuntimeException('metadata_json preexistente possui definição divergente; não será sobrescrita.');
                }
                return; // Preservada; down só removerá a coluna com o marcador próprio.
            }
            Schema::table('migration_merge_audit', function (Blueprint $table) {
                $table->json('metadata_json')->nullable()->comment(self::OWNER);
            });
        });
    }

    public function down()
    {
        $this->withAuditLock(function () {
            if (!Schema::hasTable('migration_merge_audit')) { return; }
            $column = $this->metadataColumn();
            if ($column === null || $column->column_comment !== self::OWNER) { return; }
            if (DB::table('migration_merge_audit')->whereNotNull('metadata_json')->exists()) {
                throw new \RuntimeException('Há alterações com metadados persistidos. Reverta primeiro as migrations dependentes; a auditoria não será apagada.');
            }
            Schema::table('migration_merge_audit', function (Blueprint $table) {
                $table->dropColumn('metadata_json');
            });
        });
    }

    private function metadataColumn(): ?object
    {
        return DB::selectOne(
            'SELECT DATA_TYPE AS data_type, IS_NULLABLE AS is_nullable, COLUMN_DEFAULT AS column_default, COLUMN_COMMENT AS column_comment
             FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), 'migration_merge_audit', 'metadata_json']
        );
    }

    private function withAuditLock(\Closure $operation): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new \RuntimeException('Esta migration deve ser validada em MySQL 8.0; SQLite não é equivalente.');
        }
        $version = (string) DB::selectOne('SELECT VERSION() AS version')->version;
        if (!preg_match('/^8\.0\./', $version) || stripos($version, 'mariadb') !== false) {
            throw new \RuntimeException('Este pacote foi direcionado a MySQL 8.0.x: ' . $version);
        }
        $lock = 'fersoft:ddl:' . substr(hash('sha256', DB::getDatabaseName()), 0, 40);
        $acquired = DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock]);
        if ((int) ($acquired->acquired ?? 0) !== 1) { throw new \RuntimeException('Outra execução detém o lock de conciliação.'); }
        try { $operation(); }
        finally { DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock]); }
    }
};
