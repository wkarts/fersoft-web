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

        if (!Schema::hasTable('clientes')) {
            throw new \RuntimeException('Tabela esperada ausente: clientes');
        }

        $this->ensureColumn('clientes', 'exige_rastreabilidade', function (Blueprint $table) {
            $table->boolean('exige_rastreabilidade')->default('0');
        });

        $this->ensureColumn('clientes', 'portal_nivel_visao', function (Blueprint $table) {
            $table->enum('portal_nivel_visao', ['simples', 'completo'])->default('simples');
        });

        $this->ensureColumn('clientes', 'exibe_contratos', function (Blueprint $table) {
            $table->boolean('exibe_contratos')->default('0');
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
