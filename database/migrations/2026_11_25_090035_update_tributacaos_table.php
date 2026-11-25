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

        if (!Schema::hasTable('tributacaos')) {
            throw new \RuntimeException('Tabela esperada ausente: tributacaos');
        }

        $this->ensureColumn('tributacaos', 'csll', function (Blueprint $table) {
            $table->decimal('csll', 7, 2)->nullable()->default('0.00');
        });

        $this->ensureColumn('tributacaos', 'ir', function (Blueprint $table) {
            $table->decimal('ir', 7, 2)->nullable()->default('0.00');
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
