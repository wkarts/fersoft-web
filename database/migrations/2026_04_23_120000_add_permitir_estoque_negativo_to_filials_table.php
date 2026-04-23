<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('filials') && !Schema::hasColumn('filials', 'permitir_estoque_negativo')) {
            Schema::table('filials', function (Blueprint $table) {
                $table->boolean('permitir_estoque_negativo')->nullable()->default(0)->after('email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('filials') && Schema::hasColumn('filials', 'permitir_estoque_negativo')) {
            Schema::table('filials', function (Blueprint $table) {
                $table->dropColumn('permitir_estoque_negativo');
            });
        }
    }
};
