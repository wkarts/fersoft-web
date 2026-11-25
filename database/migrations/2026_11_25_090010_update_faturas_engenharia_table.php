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

        if (!Schema::hasTable('faturas_engenharia')) {
            throw new \RuntimeException('Tabela esperada ausente: faturas_engenharia');
        }

        $this->ensureColumn('faturas_engenharia', 'valor_abatimento_compra', function (Blueprint $table) {
            $table->decimal('valor_abatimento_compra', 15, 2)->default('0.00');
        });

        $this->ensureColumn('faturas_engenharia', 'compra_id_abatida', function (Blueprint $table) {
            $table->integer('compra_id_abatida')->nullable();
        });

        $this->ensureColumn('faturas_engenharia', 'grupo_faturamento', function (Blueprint $table) {
            $table->enum('grupo_faturamento', ['servico', 'locacao', 'frete', 'geral'])->default('geral');
        });

        $this->ensureColumn('faturas_engenharia', 'status_aprovacao', function (Blueprint $table) {
            $table->enum('status_aprovacao', ['pendente', 'aprovado', 'rejeitado'])->default('pendente');
        });

        $this->ensureColumn('faturas_engenharia', 'motivo_rejeicao', function (Blueprint $table) {
            $table->text('motivo_rejeicao')->nullable();
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
