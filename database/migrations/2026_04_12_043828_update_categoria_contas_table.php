<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateCategoriaContasTable extends Migration
{
    public function up()
    {
        Schema::table('categoria_contas', function (Blueprint $table) {
            if (!Schema::hasColumn('categoria_contas', 'usuario_id')) {
                $table->unsignedInteger('usuario_id')->nullable()->after('empresa_id');
            }

            if (!Schema::hasColumn('categoria_contas', 'filial_id')) {
                $table->unsignedInteger('filial_id')->nullable()->after('usuario_id');
            }

            if (!Schema::hasColumn('categoria_contas', 'dre_grupo')) {
                $table->string('dre_grupo', 50)
                    ->nullable()
                    ->comment('Grupo da DRE: receita, despesa_adm, etc')
                    ->after('updated_at');
            }

            if (!Schema::hasColumn('categoria_contas', 'incluir_resultado')) {
                $table->boolean('incluir_resultado')
                    ->default(1)
                    ->after('dre_grupo');
            }

            if (!Schema::hasColumn('categoria_contas', 'deleted_at')) {
                $table->softDeletes()->after('incluir_resultado');
            }
        });

        // índices auxiliares
        Schema::table('categoria_contas', function (Blueprint $table) {
            try {
                $table->index('empresa_id', 'idx_categoria_contas_empresa_id');
            } catch (\Throwable $e) {
            }

            try {
                $table->index('usuario_id', 'idx_categoria_contas_usuario_id');
            } catch (\Throwable $e) {
            }

            try {
                $table->index('filial_id', 'idx_categoria_contas_filial_id');
            } catch (\Throwable $e) {
            }

            try {
                $table->index('dre_grupo', 'idx_categoria_contas_dre_grupo');
            } catch (\Throwable $e) {
            }

            try {
                $table->index('incluir_resultado', 'idx_categoria_contas_incluir_resultado');
            } catch (\Throwable $e) {
            }
        });

        // foreign keys
        $this->addForeignIfNotExists(
            'categoria_contas',
            'categoria_contas_empresa_id_foreign',
            'empresa_id',
            'empresas',
            'id',
            'cascade'
        );

        $this->addForeignIfNotExists(
            'categoria_contas',
            'categoria_contas_usuario_id_foreign',
            'usuario_id',
            'usuarios',
            'id',
            'cascade'
        );

        $this->addForeignIfNotExists(
            'categoria_contas',
            'categoria_contas_filial_id_foreign',
            'filial_id',
            'filials',
            'id',
            'cascade'
        );
    }

    public function down()
    {
        // remove foreign keys primeiro
        $this->dropForeignIfExists('categoria_contas', 'categoria_contas_filial_id_foreign');
        $this->dropForeignIfExists('categoria_contas', 'categoria_contas_usuario_id_foreign');
        // empresa_id normalmente já existia; só remova se tiver certeza que foi criada por esta migration
        // $this->dropForeignIfExists('categoria_contas', 'categoria_contas_empresa_id_foreign');

        Schema::table('categoria_contas', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_categoria_contas_incluir_resultado');
            } catch (\Throwable $e) {
            }

            try {
                $table->dropIndex('idx_categoria_contas_dre_grupo');
            } catch (\Throwable $e) {
            }

            try {
                $table->dropIndex('idx_categoria_contas_filial_id');
            } catch (\Throwable $e) {
            }

            try {
                $table->dropIndex('idx_categoria_contas_usuario_id');
            } catch (\Throwable $e) {
            }

            try {
                $table->dropIndex('idx_categoria_contas_empresa_id');
            } catch (\Throwable $e) {
            }

            if (Schema::hasColumn('categoria_contas', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            if (Schema::hasColumn('categoria_contas', 'incluir_resultado')) {
                $table->dropColumn('incluir_resultado');
            }

            if (Schema::hasColumn('categoria_contas', 'dre_grupo')) {
                $table->dropColumn('dre_grupo');
            }

            if (Schema::hasColumn('categoria_contas', 'filial_id')) {
                $table->dropColumn('filial_id');
            }

            if (Schema::hasColumn('categoria_contas', 'usuario_id')) {
                $table->dropColumn('usuario_id');
            }
        });
    }

    private function addForeignIfNotExists(
        string $table,
        string $foreignName,
        string $column,
        string $referencesTable,
        string $referencesColumn = 'id',
        string $onDelete = 'cascade'
    ): void {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        $database = DB::getDatabaseName();

        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if (!$exists) {
            Schema::table($table, function (Blueprint $table) use (
                $foreignName,
                $column,
                $referencesTable,
                $referencesColumn,
                $onDelete
            ) {
                $table->foreign($column, $foreignName)
                    ->references($referencesColumn)
                    ->on($referencesTable)
                    ->onDelete($onDelete);
            });
        }
    }

    private function dropForeignIfExists(string $table, string $foreignName): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        $database = DB::getDatabaseName();

        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists) {
            Schema::table($table, function (Blueprint $table) use ($foreignName) {
                $table->dropForeign($foreignName);
            });
        }
    }
}
