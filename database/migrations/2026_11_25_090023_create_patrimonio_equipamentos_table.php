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

        if (!Schema::hasTable('patrimonio_equipamentos')) {
            Schema::create('patrimonio_equipamentos', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->integer('id')->autoIncrement();
                $table->integer('empresa_id');
                $table->integer('produto_id')->comment('Vincula com o produto Caçamba Poli / Contêiner');
                $table->string('identificacao', 50)->comment('Ex: CA-01, CA-12, CONT-05');
                $table->enum('status', ['Patio', 'Cliente', 'Manutencao'])->default('Patio');
                $table->integer('cliente_atual_id')->nullable();
                $table->integer('contrato_id')->nullable();
                $table->string('endereco_atual', 255)->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            });
            DB::statement('ALTER TABLE `patrimonio_equipamentos` ROW_FORMAT = DYNAMIC');
        }

        $this->ensureTableCollation('patrimonio_equipamentos', 'utf8mb4_unicode_ci');
    }

    public function down()
    {
        // Migration somente evolutiva.
        return;
    }

    private function ensureTableCollation(string $tableName, string $collation): void
    {
        $current = DB::selectOne(
            'SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [DB::getDatabaseName(), $tableName]
        );
        if ($current !== null && $current->TABLE_COLLATION === $collation) {
            return;
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $collation)) {
            throw new \RuntimeException('Collation inválida.');
        }
        DB::statement('ALTER TABLE `' . $tableName . '` DEFAULT CHARACTER SET utf8mb4 COLLATE ' . $collation);
    }

};
