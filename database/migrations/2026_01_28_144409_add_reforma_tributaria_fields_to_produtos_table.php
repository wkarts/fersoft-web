<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {

            // CST_IBS_CBS VARCHAR(8) DEFAULT 'N'
            if (!Schema::hasColumn('produtos', 'CST_IBS_CBS')) {
                $table->string('CST_IBS_CBS', 8)->default('N')->after('CST_IPI');
            }

            // CLASS_TRIB_IBS_CBS VARCHAR(8)
            if (!Schema::hasColumn('produtos', 'CLASS_TRIB_IBS_CBS')) {
                $table->string('CLASS_TRIB_IBS_CBS', 8)->nullable()->after('CST_IBS_CBS');
            }

            // REDUCAO_IBS DECIMAL(15,2)
            if (!Schema::hasColumn('produtos', 'REDUCAO_IBS')) {
                $table->decimal('REDUCAO_IBS', 15, 2)->default(0)->after('CLASS_TRIB_IBS_CBS');
            }

            // REDUCAO_CBS DECIMAL(15,2)
            if (!Schema::hasColumn('produtos', 'REDUCAO_CBS')) {
                $table->decimal('REDUCAO_CBS', 15, 2)->default(0)->after('REDUCAO_IBS');
            }

            // FLAG_IS VARCHAR(1) DEFAULT 'N'
            if (!Schema::hasColumn('produtos', 'FLAG_IS')) {
                $table->string('FLAG_IS', 1)->default('N')->after('REDUCAO_CBS');
            }

            // CST_IS VARCHAR(8)
            if (!Schema::hasColumn('produtos', 'CST_IS')) {
                $table->string('CST_IS', 8)->nullable()->after('FLAG_IS');
            }

            // ALIQ_IS DECIMAL(15,3)
            if (!Schema::hasColumn('produtos', 'ALIQ_IS')) {
                $table->decimal('ALIQ_IS', 15, 3)->default(0)->after('CST_IS');
            }

            // DIFERIMENTO_IBS DECIMAL(15,4)
            if (!Schema::hasColumn('produtos', 'DIFERIMENTO_IBS')) {
                $table->decimal('DIFERIMENTO_IBS', 15, 4)->default(0)->after('ALIQ_IS');
            }

            // DIFERIMENTO_CBS DECIMAL(15,4)
            if (!Schema::hasColumn('produtos', 'DIFERIMENTO_CBS')) {
                $table->decimal('DIFERIMENTO_CBS', 15, 4)->default(0)->after('DIFERIMENTO_IBS');
            }

        });

        // Índices úteis (opcional, mas ajuda nas consultas/joins/filters)
        $this->ensureIndex('produtos', 'idx_produtos_cst_ibs_cbs', ['CST_IBS_CBS']);
        $this->ensureIndex('produtos', 'idx_produtos_class_trib_ibs_cbs', ['CLASS_TRIB_IBS_CBS']);
        $this->ensureIndex('produtos', 'idx_produtos_flag_is', ['FLAG_IS']);
        $this->ensureIndex('produtos', 'idx_produtos_cst_is', ['CST_IS']);

        // Normaliza defaults em registros antigos (idempotente)
        DB::table('produtos')->whereNull('CST_IBS_CBS')->update(['CST_IBS_CBS' => 'N']);
        DB::table('produtos')->whereNull('FLAG_IS')->update(['FLAG_IS' => 'N']);

        foreach (['REDUCAO_IBS','REDUCAO_CBS','ALIQ_IS','DIFERIMENTO_IBS','DIFERIMENTO_CBS'] as $col) {
            if (Schema::hasColumn('produtos', $col)) {
                DB::table('produtos')->whereNull($col)->update([$col => 0]);
            }
        }
    }

    public function down(): void
    {
        // Remove índices (se existirem)
        $this->dropIndexIfExists('produtos', 'idx_produtos_cst_ibs_cbs');
        $this->dropIndexIfExists('produtos', 'idx_produtos_class_trib_ibs_cbs');
        $this->dropIndexIfExists('produtos', 'idx_produtos_flag_is');
        $this->dropIndexIfExists('produtos', 'idx_produtos_cst_is');

        Schema::table('produtos', function (Blueprint $table) {
            $cols = [
                'CST_IBS_CBS',
                'CLASS_TRIB_IBS_CBS',
                'REDUCAO_IBS',
                'REDUCAO_CBS',
                'FLAG_IS',
                'CST_IS',
                'ALIQ_IS',
                'DIFERIMENTO_IBS',
                'DIFERIMENTO_CBS',
            ];

            $toDrop = [];
            foreach ($cols as $c) {
                if (Schema::hasColumn('produtos', $c)) {
                    $toDrop[] = $c;
                }
            }

            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }

    // -----------------------
    // Helpers de índice
    // -----------------------

    private function ensureIndex(string $table, string $indexName, array $columns): void
    {
        if (!$this->indexExists($table, $indexName)) {
            try {
                Schema::table($table, fn (Blueprint $t) => $t->index($columns, $indexName));
            } catch (\Throwable $e) {
                // idempotente: ignora
            }
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            try {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($indexName));
            } catch (\Throwable $e) {
                // idempotente: ignora
            }
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
};
