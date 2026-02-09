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
            if (!Schema::hasColumn('produtos', 'cst_ibs_cbs')) {
                $table->string('cst_ibs_cbs', 8)->default('N')->after('cst_ipi');
            }

            // CLASS_TRIB_IBS_CBS VARCHAR(8)
            if (!Schema::hasColumn('produtos', 'class_trib_ibs_cbs')) {
                $table->string('class_trib_ibs_cbs', 8)->nullable()->after('cst_ibs_cbs');
            }

            // REDUCAO_IBS DECIMAL(15,2)
            if (!Schema::hasColumn('produtos', 'reducao_ibs')) {
                $table->decimal('reducao_ibs', 15, 2)->default(0)->after('class_trib_ibs_cbs');
            }

            // REDUCAO_CBS DECIMAL(15,2)
            if (!Schema::hasColumn('produtos', 'reducao_cbs')) {
                $table->decimal('reducao_cbs', 15, 2)->default(0)->after('reducao_ibs');
            }

            // FLAG_IS VARCHAR(1) DEFAULT 'N'
            if (!Schema::hasColumn('produtos', 'flag_is')) {
                $table->string('flag_is', 1)->default('N')->after('reducao_cbs');
            }

            // CST_IS VARCHAR(8)
            if (!Schema::hasColumn('produtos', 'cst_is')) {
                $table->string('cst_is', 8)->nullable()->after('flag_is');
            }

            // ALIQ_IS DECIMAL(15,3)
            if (!Schema::hasColumn('produtos', 'aliq_is')) {
                $table->decimal('aliq_is', 15, 3)->default(0)->after('cst_is');
            }

            // DIFERIMENTO_IBS DECIMAL(15,4)
            if (!Schema::hasColumn('produtos', 'diferimento_ibs')) {
                $table->decimal('diferimento_ibs', 15, 4)->default(0)->after('aliq_is');
            }

            // DIFERIMENTO_CBS DECIMAL(15,4)
            if (!Schema::hasColumn('produtos', 'diferimento_cbs')) {
                $table->decimal('diferimento_cbs', 15, 4)->default(0)->after('diferimento_ibs');
            }

        });

        // Índices úteis (opcional, mas ajuda nas consultas/joins/filters)
        $this->ensureIndex('produtos', 'idx_produtos_cst_ibs_cbs', ['cst_ibs_cbs']);
        $this->ensureIndex('produtos', 'idx_produtos_class_trib_ibs_cbs', ['class_trib_ibs_cbs']);
        $this->ensureIndex('produtos', 'idx_produtos_flag_is', ['flag_is']);
        $this->ensureIndex('produtos', 'idx_produtos_cst_is', ['CST_IS']);

        // Normaliza defaults em registros antigos (idempotente)
        DB::table('produtos')->whereNull('cst_ibs_cbs')->update(['cst_ibs_cbs' => 'N']);
        DB::table('produtos')->whereNull('flag_is')->update(['flag_is' => 'N']);

        foreach (['reducao_ibs','reducao_cbs','aliq_is','diferimento_ibs','diferimento_cbs'] as $col) {
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
                'cst_ibs_cbs',
                'class_trib_ibs_cbs',
                'reducao_ibs',
                'reducao_cbs',
                'flag_is',
                'cst_is',
                'aliq_is',
                'diferimento_ibs',
                'diferimento_cbs',
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
