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

        if (!Schema::hasTable('movimentacao_destinos')) {
            throw new \RuntimeException('Tabela esperada ausente: movimentacao_destinos');
        }

        $this->ensureColumn('movimentacao_destinos', 'cobrar_frete', function (Blueprint $table) {
            $table->boolean('cobrar_frete')->default('1')->comment('1 = Sim (cobra na medição), 0 = Não (cortesia/embutido)');
        });

        $this->ensureColumn('movimentacao_destinos', 'cacamba_colocada_id', function (Blueprint $table) {
            $table->integer('cacamba_colocada_id')->nullable()->comment('Caçamba vazia deixada no cliente');
        });

        $this->ensureColumn('movimentacao_destinos', 'cacamba_retirada_id', function (Blueprint $table) {
            $table->integer('cacamba_retirada_id')->nullable()->comment('Caçamba cheia retirada do cliente');
        });

        $this->ensureColumn('movimentacao_destinos', 'is_aterro', function (Blueprint $table) {
            $table->boolean('is_aterro')->nullable()->default('0');
        });

        $this->ensureColumn('movimentacao_destinos', 'tipo_operacao_caixa', function (Blueprint $table) {
            $table->enum('tipo_operacao_caixa', ['nenhuma', 'colocar', 'retirar', 'trocar', 'remanejar'])->nullable()->default('nenhuma');
        });

        $this->ensureColumn('movimentacao_destinos', 'caixa_deixada_id', function (Blueprint $table) {
            $table->integer('caixa_deixada_id')->nullable();
        });

        $this->ensureColumn('movimentacao_destinos', 'caixa_retirada_id', function (Blueprint $table) {
            $table->integer('caixa_retirada_id')->nullable();
        });

        $this->ensureColumn('movimentacao_destinos', 'produto_residuo_id', function (Blueprint $table) {
            $table->integer('produto_residuo_id')->nullable();
        });

        $this->ensureColumn('movimentacao_destinos', 'quantidade_residuo', function (Blueprint $table) {
            $table->decimal('quantidade_residuo', 12, 3)->nullable()->comment('Peso em KG/Ton ou Volume');
        });

        $this->ensureColumn('movimentacao_destinos', 'unidade_residuo', function (Blueprint $table) {
            $table->string('unidade_residuo', 10)->nullable()->default('KG');
        });

        $this->ensureColumn('movimentacao_destinos', 'foto_ticket_balanca', function (Blueprint $table) {
            $table->string('foto_ticket_balanca', 255)->nullable();
        });

        $this->ensureColumn('movimentacao_destinos', 'alerta_mtr_enviado', function (Blueprint $table) {
            $table->boolean('alerta_mtr_enviado')->nullable()->default('0');
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
