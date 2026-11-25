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

        if (!Schema::hasTable('item_vendas')) {
            throw new \RuntimeException('Tabela esperada ausente: item_vendas');
        }

        $this->ensureColumn('item_vendas', 'cod_ajuste_icms', function (Blueprint $table) {
            $table->string('cod_ajuste_icms', 15)->nullable();
        });

        $this->ensureColumn('item_vendas', 'cod_ajuste_doc', function (Blueprint $table) {
            $table->string('cod_ajuste_doc', 15)->nullable();
        });

        $this->ensureColumn('item_vendas', 'perc_cred_presumido', function (Blueprint $table) {
            $table->decimal('perc_cred_presumido', 5, 2)->default('0.00');
        });

        $this->ensureColumn('item_vendas', 'valor_cred_presumido', function (Blueprint $table) {
            $table->decimal('valor_cred_presumido', 15, 2)->default('0.00');
        });

        $this->ensureColumn('item_vendas', 'nDraw', function (Blueprint $table) {
            $table->string('nDraw', 20)->nullable();
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
