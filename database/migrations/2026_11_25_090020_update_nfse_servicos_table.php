<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new \RuntimeException('Esta migration requer MySQL.');
        }

        if (!Schema::hasTable('nfse_servicos')) {
            throw new \RuntimeException('Tabela esperada ausente: nfse_servicos');
        }

        $this->ensureColumn('nfse_servicos', 'aliq_ibs_uf', function (Blueprint $table) {
            $table->decimal('aliq_ibs_uf', 7, 2)->nullable()->default('0.10');
        });

        $this->ensureColumn('nfse_servicos', 'aliq_cbs', function (Blueprint $table) {
            $table->decimal('aliq_cbs', 7, 2)->nullable()->default('0.90');
        });
    }

    public function down()
    {
        // Migration somente evolutiva.
        return;
    }

    protected function ensureColumn(string $tableName, string $columnName, \Closure $definition): void
    {
        if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($definition) {
            $definition($table);
        });
    }

};
