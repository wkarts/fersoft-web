<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ($this->ensureColumn('veiculos', 'data_ultima_manutencao')) {
            Schema::table('veiculos', function (Blueprint $table) {
                $table->date('data_ultima_manutencao')->nullable()->after('quilometragem');
            });
        }

        if ($this->ensureColumn('veiculos', 'quilometragem_ultima_manutencao')) {
            Schema::table('veiculos', function (Blueprint $table) {
                $table->decimal('quilometragem_ultima_manutencao', 10, 2)->nullable()->after('data_ultima_manutencao');
            });
        }

        if ($this->ensureColumn('veiculos', 'proxima_manutencao_km')) {
            Schema::table('veiculos', function (Blueprint $table) {
                $table->decimal('proxima_manutencao_km', 10, 2)->nullable()->after('quilometragem_ultima_manutencao');
            });
        }

        if ($this->ensureColumn('veiculos', 'data_proxima_revisao')) {
            Schema::table('veiculos', function (Blueprint $table) {
                $table->date('data_proxima_revisao')->nullable()->after('proxima_manutencao_km');
            });
        }

        if ($this->ensureColumn('veiculos', 'status_manutencao')) {
            Schema::table('veiculos', function (Blueprint $table) {
                $table->string('status_manutencao', 30)->default('Em dia')->after('data_proxima_revisao');
            });
        }

        if ($this->ensureColumn('veiculos', 'observacoes_manutencao')) {
            Schema::table('veiculos', function (Blueprint $table) {
                $table->text('observacoes_manutencao')->nullable()->after('status_manutencao');
            });
        }

        if ($this->ensureColumn('manutencoes', 'tipo')) {
            Schema::table('manutencoes', function (Blueprint $table) {
                $table->string('tipo', 60)->nullable()->after('descricao');
            });
        }

        if ($this->ensureColumn('manutencoes', 'quilometragem_atual')) {
            Schema::table('manutencoes', function (Blueprint $table) {
                $table->decimal('quilometragem_atual', 10, 2)->nullable()->after('data_manutencao');
            });
        }

        if ($this->ensureColumn('manutencoes', 'proxima_manutencao_km')) {
            Schema::table('manutencoes', function (Blueprint $table) {
                $table->decimal('proxima_manutencao_km', 10, 2)->nullable()->after('quilometragem_atual');
            });
        }

        if ($this->ensureColumn('manutencoes', 'proxima_manutencao_data')) {
            Schema::table('manutencoes', function (Blueprint $table) {
                $table->date('proxima_manutencao_data')->nullable()->after('proxima_manutencao_km');
            });
        }

        if ($this->ensureColumn('manutencoes', 'fornecedor_id')) {
            Schema::table('manutencoes', function (Blueprint $table) {
                $table->unsignedInteger('fornecedor_id')->nullable()->after('responsavel_id');
            });
        }

        if ($this->columnExists('manutencoes', 'fornecedor_id') && !$this->foreignKeyExists('manutencoes', 'manutencoes_fornecedor_id_foreign')) {
            Schema::table('manutencoes', function (Blueprint $table) {
                $table->foreign('fornecedor_id')
                    ->references('id')
                    ->on('fornecedors')
                    ->onDelete('set null')
                    ->onUpdate('no action');
            });
        }

        if ($this->ensureColumn('manutencoes', 'observacoes')) {
            Schema::table('manutencoes', function (Blueprint $table) {
                $table->text('observacoes')->nullable()->after('checklist');
            });
        }

        if ($this->columnExists('manutencoes', 'status') && DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE manutencoes MODIFY status ENUM('Planejada','Em andamento','Concluída') DEFAULT 'Planejada'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->foreignKeyExists('manutencoes', 'manutencoes_fornecedor_id_foreign')) {
            Schema::table('manutencoes', function (Blueprint $table) {
                $table->dropForeign('manutencoes_fornecedor_id_foreign');
            });
        }

        if ($this->columnExists('manutencoes', 'observacoes')) {
            Schema::table('manutencoes', function (Blueprint $table) {
                $table->dropColumn('observacoes');
            });
        }

        if ($this->columnExists('manutencoes', 'fornecedor_id')) {
            Schema::table('manutencoes', function (Blueprint $table) {
                $table->dropColumn('fornecedor_id');
            });
        }

        if ($this->columnExists('manutencoes', 'proxima_manutencao_data')) {
            Schema::table('manutencoes', function (Blueprint $table) {
                $table->dropColumn('proxima_manutencao_data');
            });
        }

        if ($this->columnExists('manutencoes', 'proxima_manutencao_km')) {
            Schema::table('manutencoes', function (Blueprint $table) {
                $table->dropColumn('proxima_manutencao_km');
            });
        }

        if ($this->columnExists('manutencoes', 'quilometragem_atual')) {
            Schema::table('manutencoes', function (Blueprint $table) {
                $table->dropColumn('quilometragem_atual');
            });
        }

        if ($this->columnExists('manutencoes', 'tipo')) {
            Schema::table('manutencoes', function (Blueprint $table) {
                $table->dropColumn('tipo');
            });
        }

        if ($this->columnExists('manutencoes', 'status') && DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE manutencoes MODIFY status ENUM('Planejada','Concluída') DEFAULT 'Planejada'");
        }

        if ($this->columnExists('veiculos', 'observacoes_manutencao')) {
            Schema::table('veiculos', function (Blueprint $table) {
                $table->dropColumn('observacoes_manutencao');
            });
        }

        if ($this->columnExists('veiculos', 'status_manutencao')) {
            Schema::table('veiculos', function (Blueprint $table) {
                $table->dropColumn('status_manutencao');
            });
        }

        if ($this->columnExists('veiculos', 'data_proxima_revisao')) {
            Schema::table('veiculos', function (Blueprint $table) {
                $table->dropColumn('data_proxima_revisao');
            });
        }

        if ($this->columnExists('veiculos', 'proxima_manutencao_km')) {
            Schema::table('veiculos', function (Blueprint $table) {
                $table->dropColumn('proxima_manutencao_km');
            });
        }

        if ($this->columnExists('veiculos', 'quilometragem_ultima_manutencao')) {
            Schema::table('veiculos', function (Blueprint $table) {
                $table->dropColumn('quilometragem_ultima_manutencao');
            });
        }

        if ($this->columnExists('veiculos', 'data_ultima_manutencao')) {
            Schema::table('veiculos', function (Blueprint $table) {
                $table->dropColumn('data_ultima_manutencao');
            });
        }
    }

    private function ensureColumn(string $table, string $column): bool
    {
        return Schema::hasTable($table) && ! $this->columnExists($table, $column);
    }

    private function columnExists(string $table, string $column): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $columns = array_map('strtolower', Schema::getColumnListing($table));

        return in_array(strtolower($column), $columns);
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA foreign_key_list('{$table}')");
            foreach ($rows as $row) {
                $id = $row->id ?? null;
                if ($id === null) {
                    continue;
                }

                $candidate = strtolower($table . '_' . ($row->from ?? '') . '_foreign');
                if ($candidate === strtolower($foreignKey)) {
                    return true;
                }
            }

            return false;
        }

        $database = Schema::getConnection()->getDatabaseName();

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignKey)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
