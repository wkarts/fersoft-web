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

        if (!Schema::hasTable('servicos')) {
            throw new \RuntimeException('Tabela esperada ausente: servicos');
        }

        $this->ensureColumn('servicos', 'iss_retido', function (Blueprint $table) {
            $table->boolean('iss_retido')->default('2')->comment('1 = Sim, 2 = Não');
        });

        $this->ensureColumn('servicos', 'codigo_indicador_operacao', function (Blueprint $table) {
            $table->string('codigo_indicador_operacao', 10)->nullable()->default('050101');
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
