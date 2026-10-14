<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new \RuntimeException('Esta migration requer MySQL/MariaDB.');
        }

        if (!Schema::hasTable('mtr_configs') || !Schema::hasColumn('mtr_configs', 'senha')) {
            throw new \RuntimeException('Tabela ou coluna esperada ausente: mtr_configs.senha');
        }

        $column = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'mtr_configs')
            ->where('COLUMN_NAME', 'senha')
            ->first([
                'DATA_TYPE', 'IS_NULLABLE', 'CHARACTER_SET_NAME',
                'COLLATION_NAME', 'COLUMN_COMMENT',
            ]);

        if (!$column) {
            throw new \RuntimeException('Não foi possível verificar mtr_configs.senha.');
        }

        $type = strtolower((string) $column->DATA_TYPE);

        // Não rebaixar instalações que já possuem capacidade suficiente.
        if (in_array($type, ['text', 'mediumtext', 'longtext'], true)) {
            return;
        }

        if ($type !== 'varchar') {
            throw new \RuntimeException('Tipo inesperado em mtr_configs.senha. Nenhuma alteração efetuada.');
        }

        // O Laravel expande consideravelmente o tamanho do valor criptografado.
        // TEXT evita truncamento de tokens longos sem remover dados ou colunas.
        Schema::table('mtr_configs', function (Blueprint $table) use ($column) {
            $definition = $table->text('senha')->nullable();

            if ($column->CHARACTER_SET_NAME) {
                $definition->charset((string) $column->CHARACTER_SET_NAME);
            }

            if ($column->COLLATION_NAME) {
                $definition->collation((string) $column->COLLATION_NAME);
            }

            if ($column->COLUMN_COMMENT !== '') {
                $definition->comment((string) $column->COLUMN_COMMENT);
            }

            $definition->change();
        });
    }

    public function down(): void
    {
        // Rollback conservador: reduzir novamente para VARCHAR(500)
        // poderia truncar tokens já persistidos. Não remover ou reverter dados.
    }
};
