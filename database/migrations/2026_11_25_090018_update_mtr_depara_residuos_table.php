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

        if (!Schema::hasTable('mtr_depara_residuos')) {
            throw new \RuntimeException('Tabela esperada ausente: mtr_depara_residuos');
        }

        $this->ensureColumn('mtr_depara_residuos', 'numero_onu', function (Blueprint $table) {
            $table->string('numero_onu', 20)->nullable();
        });

        $this->ensureColumn('mtr_depara_residuos', 'classe_risco', function (Blueprint $table) {
            $table->string('classe_risco', 50)->nullable();
        });

        $this->ensureColumn('mtr_depara_residuos', 'nome_embarque', function (Blueprint $table) {
            $table->string('nome_embarque', 255)->nullable();
        });

        $this->ensureColumn('mtr_depara_residuos', 'grupo_embalagem', function (Blueprint $table) {
            $table->string('grupo_embalagem', 20)->nullable();
        });

        $this->ensureColumn('mtr_depara_residuos', 'codigo_interno', function (Blueprint $table) {
            $table->string('codigo_interno', 50)->nullable();
        });

        $this->ensureColumn('mtr_depara_residuos', 'descricao_interna', function (Blueprint $table) {
            $table->string('descricao_interna', 255)->nullable();
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
