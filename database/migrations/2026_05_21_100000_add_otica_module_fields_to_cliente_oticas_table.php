<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cliente_oticas')) {
            Schema::create('cliente_oticas', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('empresa_id')->nullable()->index();
                $table->unsignedInteger('filial_id')->nullable()->index();
                $table->unsignedInteger('usuario_id')->nullable()->index();
                $table->unsignedInteger('cliente_id')->nullable()->index();
                $table->unsignedInteger('lente_id')->nullable()->index();
                $table->unsignedInteger('armacao_id')->nullable()->index();
                $table->unsignedInteger('venda_id')->nullable()->index();
                $table->string('status', 40)->nullable()->default('orcamento');
                $table->date('data')->nullable();
                $table->date('data_entrega')->nullable();
                $table->string('medico', 120)->nullable();
                $table->string('armacao', 150)->nullable();
                $table->string('lente', 150)->nullable();
                $table->string('tipo_lente', 100)->nullable();
                $table->string('tratamento', 100)->nullable();
                $table->string('forma_pagamento', 100)->nullable();
                $table->integer('previsao_retorno_dias')->nullable();
                $table->integer('qtd_armacao')->nullable()->default(1);
                $table->integer('qtd_lente')->nullable()->default(1);
                $table->decimal('valor_armacao', 15, 2)->nullable()->default(0);
                $table->decimal('valor_lente', 15, 2)->nullable()->default(0);
                $table->text('observacao')->nullable();
                $this->addReceitaColumns($table);
                $table->timestamps();
                $table->softDeletes();
            });
            return;
        }

        Schema::table('cliente_oticas', function (Blueprint $table) {
            $this->addColumnIfMissing($table, 'empresa_id', fn () => $table->unsignedInteger('empresa_id')->nullable()->index());
            $this->addColumnIfMissing($table, 'filial_id', fn () => $table->unsignedInteger('filial_id')->nullable()->index());
            $this->addColumnIfMissing($table, 'usuario_id', fn () => $table->unsignedInteger('usuario_id')->nullable()->index());
            $this->addColumnIfMissing($table, 'lente_id', fn () => $table->unsignedInteger('lente_id')->nullable()->index());
            $this->addColumnIfMissing($table, 'armacao_id', fn () => $table->unsignedInteger('armacao_id')->nullable()->index());
            $this->addColumnIfMissing($table, 'venda_id', fn () => $table->unsignedInteger('venda_id')->nullable()->index());
            $this->addColumnIfMissing($table, 'status', fn () => $table->string('status', 40)->nullable()->default('orcamento'));
            $this->addColumnIfMissing($table, 'data_entrega', fn () => $table->date('data_entrega')->nullable());
            $this->addColumnIfMissing($table, 'forma_pagamento', fn () => $table->string('forma_pagamento', 100)->nullable());
            $this->addColumnIfMissing($table, 'cil_oe_longe', fn () => $table->string('cil_oe_longe', 20)->nullable());
            $this->addColumnIfMissing($table, 'dp_oe_longe', fn () => $table->string('dp_oe_longe', 20)->nullable());
            $this->addColumnIfMissing($table, 'dnp_oe_longe', fn () => $table->string('dnp_oe_longe', 20)->nullable());
            $this->addColumnIfMissing($table, 'adicao_oe_perto', fn () => $table->string('adicao_oe_perto', 20)->nullable());
            $this->addColumnIfMissing($table, 'altura_oe_perto', fn () => $table->string('altura_oe_perto', 20)->nullable());
            $this->addColumnIfMissing($table, 'referencia', fn () => $table->string('referencia', 100)->nullable());
            $this->addColumnIfMissing($table, 'deleted_at', fn () => $table->softDeletes());
        });

        $this->relaxRequiredColumns();
    }

    public function down(): void
    {
        // Migration incremental e idempotente: não remove dados antigos de OS/receitas óticas.
    }

    private function addColumnIfMissing(Blueprint $table, string $column, callable $definition): void
    {
        if (!Schema::hasColumn('cliente_oticas', $column)) {
            $definition();
        }
    }

    private function addReceitaColumns(Blueprint $table): void
    {
        foreach ([
            'esf_od_longe', 'cil_od_longe', 'eixo_od_longe', 'dnp_od_longe', 'dp_od_longe',
            'esf_oe_longe', 'cil_oe_longe', 'eixo_oe_longe', 'dnp_oe_longe', 'dp_oe_longe',
            'esf_od_perto', 'cil_od_perto', 'eixo_od_perto', 'adicao_od_perto', 'altura_od_perto',
            'esf_oe_perto', 'cil_oe_perto', 'eixo_oe_perto', 'adicao_oe_perto', 'altura_oe_perto',
        ] as $field) {
            $table->string($field, 20)->nullable();
        }
    }

    private function relaxRequiredColumns(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $stringFields = [
            'armacao' => '150', 'lente' => '150', 'tipo_lente' => '100', 'tratamento' => '100',
            'medico' => '120', 'observacao' => 'TEXT', 'data' => 'DATE', 'referencia' => '100',
            'esf_od_longe' => '20', 'cil_od_longe' => '20', 'eixo_od_longe' => '20', 'dnp_od_longe' => '20', 'dp_od_longe' => '20',
            'esf_oe_longe' => '20', 'cli_oe_longe' => '20', 'eixo_oe_longe' => '20', 'dnp_oe_longe' => '20',
            'esf_od_perto' => '20', 'cil_od_perto' => '20', 'eixo_od_perto' => '20', 'adicao_od_perto' => '20', 'altura_od_perto' => '20',
            'dnp_od_perto' => '20', 'dp_od_perto' => '20', 'esf_oe_perto' => '20', 'cil_oe_perto' => '20', 'eixo_oe_perto' => '20',
        ];

        foreach ($stringFields as $field => $size) {
            if (!Schema::hasColumn('cliente_oticas', $field)) {
                continue;
            }
            $type = $size === 'TEXT' ? 'TEXT' : ($size === 'DATE' ? 'DATE' : "VARCHAR({$size})");
            try { DB::statement("ALTER TABLE cliente_oticas MODIFY {$field} {$type} NULL"); } catch (\Throwable $e) {}
        }

        foreach (['qtd_armacao', 'qtd_lente', 'previsao_retorno_dias'] as $field) {
            if (Schema::hasColumn('cliente_oticas', $field)) {
                try { DB::statement("ALTER TABLE cliente_oticas MODIFY {$field} INT NULL"); } catch (\Throwable $e) {}
            }
        }

        foreach (['valor_armacao', 'valor_lente'] as $field) {
            if (Schema::hasColumn('cliente_oticas', $field)) {
                try { DB::statement("ALTER TABLE cliente_oticas MODIFY {$field} DECIMAL(15,2) NULL DEFAULT 0"); } catch (\Throwable $e) {}
            }
        }
    }
};
