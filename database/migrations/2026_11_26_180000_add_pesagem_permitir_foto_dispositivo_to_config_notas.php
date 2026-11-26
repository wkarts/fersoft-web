<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('config_notas', 'pesagem_permitir_foto_dispositivo')) {
            Schema::table('config_notas', function (Blueprint $table) {
                $table->boolean('pesagem_permitir_foto_dispositivo')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('config_notas', 'pesagem_permitir_foto_dispositivo')) {
            Schema::table('config_notas', function (Blueprint $table) {
                $table->dropColumn('pesagem_permitir_foto_dispositivo');
            });
        }
    }
};
