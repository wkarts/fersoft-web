<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conta_empresa_filials')) {
            foreach (['id', 'conta_empresa_id', 'filial_id', 'created_at', 'updated_at'] as $column) {
                if (!Schema::hasColumn('conta_empresa_filials', $column)) {
                    throw new \RuntimeException('A tabela conta_empresa_filials já existe, porém está incompleta: ' . $column);
                }
            }
            return;
        }
        if (!Schema::hasTable('conta_empresas')) {
            throw new \RuntimeException('Tabela referenciada ausente: conta_empresas');
        }
        Schema::create('conta_empresa_filials', function (Blueprint $table) {
            $table->increments('id');
            // INT UNSIGNED: igual a conta_empresas.id da estrutura ARGWS.
            $table->unsignedInteger('conta_empresa_id');
            $table->unsignedInteger('filial_id')->nullable();
            $table->timestamps();

            $table->index('conta_empresa_id', 'ce_filial_conta_id_foreign');
            $table->foreign('conta_empresa_id', 'ce_filial_conta_id_foreign')
                ->references('id')->on('conta_empresas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Rollback conservador: nenhum DROP e nenhuma remoção de dados ou de campos.
        // Os objetos acrescentados permanecem disponíveis após migrate:rollback.
    }
};
