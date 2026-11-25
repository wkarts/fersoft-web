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

        if (!Schema::hasTable('solicitacoes_coleta')) {
            throw new \RuntimeException('Tabela esperada ausente: solicitacoes_coleta');
        }

        $this->ensureColumn('solicitacoes_coleta', 'data_reserva', function (Blueprint $table) {
            $table->date('data_reserva')->nullable();
        });

        $this->ensureColumn('solicitacoes_coleta', 'turno', function (Blueprint $table) {
            $table->string('turno', 20)->nullable()->comment('manha ou tarde');
        });

        $this->ensureColumn('solicitacoes_coleta', 'tipo_veiculo', function (Blueprint $table) {
            $table->string('tipo_veiculo', 100)->nullable();
        });

        $this->ensureColumn('solicitacoes_coleta', 'produto_residuo_id', function (Blueprint $table) {
            $table->integer('produto_residuo_id')->nullable();
        });

        $this->ensureColumn('solicitacoes_coleta', 'produto_cacamba_id', function (Blueprint $table) {
            $table->integer('produto_cacamba_id')->nullable();
        });

        $this->ensureColumn('solicitacoes_coleta', 'qtd_cacambas', function (Blueprint $table) {
            $table->integer('qtd_cacambas')->nullable()->default('1');
        });

        $this->ensureColumn('solicitacoes_coleta', 'peso_estimado', function (Blueprint $table) {
            $table->decimal('peso_estimado', 12, 3)->nullable();
        });

        $this->ensureColumn('solicitacoes_coleta', 'unidade_peso', function (Blueprint $table) {
            $table->string('unidade_peso', 10)->nullable()->default('KG');
        });

        $this->ensureColumn('solicitacoes_coleta', 'tempo_franquia_horas', function (Blueprint $table) {
            $table->decimal('tempo_franquia_horas', 5, 2)->nullable();
        });

        $this->ensureColumn('solicitacoes_coleta', 'valor_taxa_extra', function (Blueprint $table) {
            $table->decimal('valor_taxa_extra', 10, 2)->nullable();
        });

        $this->ensureColumn('solicitacoes_coleta', 'aceitou_termo_taxa', function (Blueprint $table) {
            $table->boolean('aceitou_termo_taxa')->nullable()->default('1');
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
