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

        if (!Schema::hasTable('config_notas')) {
            throw new \RuntimeException('Tabela esperada ausente: config_notas');
        }

        $this->ensureColumn('config_notas', 'usuario_saatri', function (Blueprint $table) {
            $table->string('usuario_saatri', 100)->nullable();
        });

        $this->ensureColumn('config_notas', 'senha_saatri', function (Blueprint $table) {
            $table->string('senha_saatri', 100)->nullable();
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
