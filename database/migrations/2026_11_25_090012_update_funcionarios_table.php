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

        if (!Schema::hasTable('funcionarios')) {
            throw new \RuntimeException('Tabela esperada ausente: funcionarios');
        }

        $this->ensureColumn('funcionarios', 'recebe_alerta_frota', function (Blueprint $table) {
            $table->boolean('recebe_alerta_frota')->nullable()->default('0');
        });

        $this->ensureColumn('funcionarios', 'responsavel_mtr', function (Blueprint $table) {
            $table->boolean('responsavel_mtr')->nullable()->default('0');
        });

        $this->ensureColumn('funcionarios', 'permissao_mtr_whatsapp', function (Blueprint $table) {
            $table->boolean('permissao_mtr_whatsapp')->nullable()->default('0');
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
