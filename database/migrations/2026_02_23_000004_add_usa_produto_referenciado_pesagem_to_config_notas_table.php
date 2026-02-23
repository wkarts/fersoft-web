<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('config_notas')) {
            return;
        }

        Schema::table('config_notas', function (Blueprint $table) {
            if (!Schema::hasColumn('config_notas', 'usa_produto_referenciado_pesagem')) {
                $table->boolean('usa_produto_referenciado_pesagem')
                    ->default(false)
                    ->after('usar_valores_ticket_pesagem');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('config_notas')) {
            return;
        }

        Schema::table('config_notas', function (Blueprint $table) {
            if (Schema::hasColumn('config_notas', 'usa_produto_referenciado_pesagem')) {
                $table->dropColumn('usa_produto_referenciado_pesagem');
            }
        });
    }
};
