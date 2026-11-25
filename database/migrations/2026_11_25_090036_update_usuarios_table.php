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

        if (!Schema::hasTable('usuarios')) {
            throw new \RuntimeException('Tabela esperada ausente: usuarios');
        }

        $this->ensureColumn('usuarios', 'cpf_mtr', function (Blueprint $table) {
            $table->string('cpf_mtr', 14)->nullable();
        });

        $this->ensureColumn('usuarios', 'token_mtr', function (Blueprint $table) {
            $table->text('token_mtr')->nullable();
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
