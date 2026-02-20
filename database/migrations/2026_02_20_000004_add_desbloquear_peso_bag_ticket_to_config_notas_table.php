<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('config_notas')) {
            return;
        }

        Schema::table('config_notas', function (Blueprint $table) {
            if (!Schema::hasColumn('config_notas', 'desbloquear_campo_peso_bag_ticket')) {
                $table->boolean('desbloquear_campo_peso_bag_ticket')
                    ->default(false)
                    ->after('exibir_valores_ticket_pesagem_grid');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('config_notas')) {
            return;
        }

        Schema::table('config_notas', function (Blueprint $table) {
            if (Schema::hasColumn('config_notas', 'desbloquear_campo_peso_bag_ticket')) {
                $table->dropColumn('desbloquear_campo_peso_bag_ticket');
            }
        });
    }
};
