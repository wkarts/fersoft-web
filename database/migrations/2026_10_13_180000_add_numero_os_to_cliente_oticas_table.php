<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cliente_oticas')) {
            throw new \RuntimeException('Tabela esperada ausente: cliente_oticas');
        }

        if (!Schema::hasColumn('cliente_oticas', 'id')) {
            throw new \RuntimeException('Coluna esperada ausente: cliente_oticas.id');
        }

        // Evita duplicar a coluna em bancos que já receberam a alteração.
        if (!Schema::hasColumn('cliente_oticas', 'numero_os')) {
            Schema::table('cliente_oticas', function (Blueprint $table) {
                $table->string('numero_os', 20)->nullable()->after('id');
            });
        }
    }

    public function down(): void
    {
        // Rollback conservador: não remover numero_os nem os dados gravados.
        // Mantém o padrão de preservação das migrations existentes.
    }
};
