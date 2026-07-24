<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUNA = 'pesagem_exibir_chave_pix_relatorio';

    public function up(): void
    {
        if (Schema::hasTable('config_notas') && !Schema::hasColumn('config_notas', self::COLUNA)) {
            Schema::table('config_notas', function (Blueprint $table) {
                $table->boolean(self::COLUNA)->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('config_notas') && Schema::hasColumn('config_notas', self::COLUNA)) {
            Schema::table('config_notas', function (Blueprint $table) {
                $table->dropColumn(self::COLUNA);
            });
        }
    }
};
