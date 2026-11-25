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

        if (!Schema::hasTable('pesagens')) {
            throw new \RuntimeException('Tabela esperada ausente: pesagens');
        }

        $this->ensureColumn('pesagens', 'movimentacao_id', function (Blueprint $table) {
            $table->unsignedBigInteger('movimentacao_id')->nullable();
        });

        $this->ensureColumn('pesagens', 'nfe_entrada', function (Blueprint $table) {
            $table->string('nfe_entrada', 60)->nullable();
        });

        $this->ensureColumn('pesagens', 'mtr_entrada', function (Blueprint $table) {
            $table->string('mtr_entrada', 60)->nullable();
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
