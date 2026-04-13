<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MergeContaEmpresasTable extends Migration
{
    public function up()
    {
        $this->ensureMergeAuditTable();

        if (!Schema::hasTable('conta_empresas')) {
            return;
        }

        $addFilialId = !Schema::hasColumn('conta_empresas', 'filial_id');

        Schema::table('conta_empresas', function (Blueprint $table) use ($addFilialId) {
            if ($addFilialId) {
                $table->integer('filial_id')->nullable();
            }
        });

        if ($addFilialId) {
            $this->markCreated('column', 'conta_empresas.filial_id');
        }
    }

    public function down()
    {
        $this->ensureMergeAuditTable();

        if (!Schema::hasTable('conta_empresas')) {
            return;
        }

        if ($this->wasCreated('column', 'conta_empresas.filial_id') && Schema::hasColumn('conta_empresas', 'filial_id')) {
            Schema::table('conta_empresas', function (Blueprint $table) {
                $table->dropColumn('filial_id');
            });

            $this->forgetCreated('column', 'conta_empresas.filial_id');
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
}
