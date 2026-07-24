<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUNAS = [
        'pesagem_permitir_cadastro_rapido_cliente',
        'pesagem_permitir_cadastro_rapido_fornecedor',
        'pesagem_permitir_cadastro_rapido_veiculo',
        'pesagem_permitir_cadastro_rapido_motorista',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('config_notas')) {
            return;
        }

        Schema::table('config_notas', function (Blueprint $table) {
            foreach (self::COLUNAS as $coluna) {
                if (!Schema::hasColumn('config_notas', $coluna)) {
                    $table->boolean($coluna)->default(false);
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('config_notas')) {
            return;
        }

        Schema::table('config_notas', function (Blueprint $table) {
            foreach (self::COLUNAS as $coluna) {
                if (Schema::hasColumn('config_notas', $coluna)) {
                    $table->dropColumn($coluna);
                }
            }
        });
    }
};
