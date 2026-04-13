<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeStockMovementsTable extends Migration
{
    public function up()
    {
        $this->ensureMergeAuditTable();

        if (!Schema::hasTable('stock_movements')) {
            return;
        }

        $addSaldoMomento = !Schema::hasColumn('stock_movements', 'saldo_momento');

        Schema::table('stock_movements', function (Blueprint $table) use ($addSaldoMomento) {
            if ($addSaldoMomento) {
                $table->decimal('saldo_momento', 16, 7)
                    ->default(0.0000000)
                    ->comment('Saldo logo após esta movimentação');
            }
        });

        if ($addSaldoMomento) {
            $this->markCreated('column', 'stock_movements.saldo_momento');
        }
    }

    public function down()
    {
        $this->ensureMergeAuditTable();

        if (!Schema::hasTable('stock_movements')) {
            return;
        }

        if (
            $this->wasCreated('column', 'stock_movements.saldo_momento') &&
            Schema::hasColumn('stock_movements', 'saldo_momento')
        ) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropColumn('saldo_momento');
            });

            $this->forgetCreated('column', 'stock_movements.saldo_momento');
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
