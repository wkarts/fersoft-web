<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('usuarios')) {
            return;
        }

        Schema::table('usuarios', function (Blueprint $table) {
            if (!Schema::hasColumn('usuarios', 'balanca_padrao_id')) {
                $table->unsignedInteger('balanca_padrao_id')
                    ->nullable()
                    ->after('local_padrao')
                    ->index('usuarios_balanca_padrao_id_index');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('usuarios')) {
            return;
        }

        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'balanca_padrao_id')) {
                $table->dropColumn('balanca_padrao_id');
            }
        });
    }
};
