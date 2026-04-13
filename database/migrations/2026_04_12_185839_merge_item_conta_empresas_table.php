<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeItemContaEmpresasTable extends Migration
{
    public function up()
    {
        $this->ensureMergeAuditTable();

        if (!Schema::hasTable('item_conta_empresas')) {
            return;
        }

        $addContaPagarId = !Schema::hasColumn('item_conta_empresas', 'conta_pagar_id');
        $addCategoriaId = !Schema::hasColumn('item_conta_empresas', 'categoria_id');
        $addOrigem = !Schema::hasColumn('item_conta_empresas', 'origem');
        $addUserId = !Schema::hasColumn('item_conta_empresas', 'user_id');
        $addEmpresaId = !Schema::hasColumn('item_conta_empresas', 'empresa_id');
        $addContaReceberId = !Schema::hasColumn('item_conta_empresas', 'conta_receber_id');

        Schema::table('item_conta_empresas', function (Blueprint $table) use (
            $addContaPagarId,
            $addCategoriaId,
            $addOrigem,
            $addUserId,
            $addEmpresaId,
            $addContaReceberId
        ) {
            if ($addContaPagarId) {
                $table->unsignedBigInteger('conta_pagar_id')->nullable();
            }

            if ($addCategoriaId) {
                $table->integer('categoria_id')->nullable();
            }

            if ($addOrigem) {
                $table->string('origem', 20)->nullable()->default('manual');
            }

            if ($addUserId) {
                $table->integer('user_id')->nullable();
            }

            if ($addEmpresaId) {
                $table->integer('empresa_id')->nullable();
            }

            if ($addContaReceberId) {
                $table->integer('conta_receber_id')->nullable();
            }
        });

        if ($addContaPagarId) {
            $this->markCreated('column', 'item_conta_empresas.conta_pagar_id');
        }
        if ($addCategoriaId) {
            $this->markCreated('column', 'item_conta_empresas.categoria_id');
        }
        if ($addOrigem) {
            $this->markCreated('column', 'item_conta_empresas.origem');
        }
        if ($addUserId) {
            $this->markCreated('column', 'item_conta_empresas.user_id');
        }
        if ($addEmpresaId) {
            $this->markCreated('column', 'item_conta_empresas.empresa_id');
        }
        if ($addContaReceberId) {
            $this->markCreated('column', 'item_conta_empresas.conta_receber_id');
        }
    }

    public function down()
    {
        $this->ensureMergeAuditTable();

        if (!Schema::hasTable('item_conta_empresas')) {
            return;
        }

        $columnsToDrop = [];

        if ($this->wasCreated('column', 'item_conta_empresas.conta_receber_id') && Schema::hasColumn('item_conta_empresas', 'conta_receber_id')) {
            $columnsToDrop[] = 'conta_receber_id';
        }

        if ($this->wasCreated('column', 'item_conta_empresas.empresa_id') && Schema::hasColumn('item_conta_empresas', 'empresa_id')) {
            $columnsToDrop[] = 'empresa_id';
        }

        if ($this->wasCreated('column', 'item_conta_empresas.user_id') && Schema::hasColumn('item_conta_empresas', 'user_id')) {
            $columnsToDrop[] = 'user_id';
        }

        if ($this->wasCreated('column', 'item_conta_empresas.origem') && Schema::hasColumn('item_conta_empresas', 'origem')) {
            $columnsToDrop[] = 'origem';
        }

        if ($this->wasCreated('column', 'item_conta_empresas.categoria_id') && Schema::hasColumn('item_conta_empresas', 'categoria_id')) {
            $columnsToDrop[] = 'categoria_id';
        }

        if ($this->wasCreated('column', 'item_conta_empresas.conta_pagar_id') && Schema::hasColumn('item_conta_empresas', 'conta_pagar_id')) {
            $columnsToDrop[] = 'conta_pagar_id';
        }

        if (!empty($columnsToDrop)) {
            Schema::table('item_conta_empresas', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }

        foreach ([
                     'item_conta_empresas.conta_receber_id',
                     'item_conta_empresas.empresa_id',
                     'item_conta_empresas.user_id',
                     'item_conta_empresas.origem',
                     'item_conta_empresas.categoria_id',
                     'item_conta_empresas.conta_pagar_id',
                 ] as $item) {
            $this->forgetCreated('column', $item);
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
