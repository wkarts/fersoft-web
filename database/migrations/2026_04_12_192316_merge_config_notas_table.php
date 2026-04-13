<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeConfigNotasTable extends Migration
{
    public function up()
    {
        $this->ensureMergeAuditTable();

        if (!Schema::hasTable('config_notas')) {
            return;
        }

        $addPermitirEstoqueNegativo = !Schema::hasColumn('config_notas', 'permitir_estoque_negativo');

        Schema::table('config_notas', function (Blueprint $table) use ($addPermitirEstoqueNegativo) {
            if ($addPermitirEstoqueNegativo) {
                $table->boolean('permitir_estoque_negativo')->nullable()->default(0);
            }
        });

        if ($addPermitirEstoqueNegativo) {
            $this->markCreated('column', 'config_notas.permitir_estoque_negativo');
        }
    }

    public function down()
    {
        $this->ensureMergeAuditTable();

        if (!Schema::hasTable('config_notas')) {
            return;
        }

        if (
            $this->wasCreated('column', 'config_notas.permitir_estoque_negativo') &&
            Schema::hasColumn('config_notas', 'permitir_estoque_negativo')
        ) {
            Schema::table('config_notas', function (Blueprint $table) {
                $table->dropColumn('permitir_estoque_negativo');
            });

            $this->forgetCreated('column', 'config_notas.permitir_estoque_negativo');
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
