<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateFechamentosMensaisTable extends Migration
{
    public function up()
    {
        $this->ensureMergeAuditTable();

        if (!Schema::hasTable('fechamentos_mensais')) {
            Schema::create('fechamentos_mensais', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('empresa_id');
                $table->integer('mes');
                $table->integer('ano');
                $table->decimal('lucro_prejuizo_liquido', 15, 2)->nullable();
                $table->enum('regime', ['competencia', 'caixa'])->default('competencia');
                $table->enum('status', ['aberto', 'encerrado'])->default('aberto');
                $table->integer('usuario_id')->nullable();
                $table->dateTime('data_fechamento')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
            });

            $this->markCreated('table', 'fechamentos_mensais');
        }

        if ($this->addIndexIfNotExists('fechamentos_mensais', 'empresa_mes_ano_regime', ['empresa_id', 'mes', 'ano', 'regime'], true)) {
            $this->markCreated('index', 'fechamentos_mensais.empresa_mes_ano_regime');
        }
    }

    public function down()
    {
        $this->ensureMergeAuditTable();

        if ($this->wasCreated('index', 'fechamentos_mensais.empresa_mes_ano_regime')) {
            $this->dropIndexIfExists('fechamentos_mensais', 'empresa_mes_ano_regime');
            $this->forgetCreated('index', 'fechamentos_mensais.empresa_mes_ano_regime');
        }

        if ($this->wasCreated('table', 'fechamentos_mensais')) {
            Schema::dropIfExists('fechamentos_mensais');
            $this->forgetCreated('table', 'fechamentos_mensais');
        }
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

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

}
