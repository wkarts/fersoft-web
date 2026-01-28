<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('conta_pagars', 'data_emissao_nfe')) {
            Schema::table('conta_pagars', function (Blueprint $table) {
                // date é suficiente pra emissão (se quiser datetime, me avise)
                $table->date('data_emissao_nfe')->nullable()->after('numero_nota_fiscal');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('conta_pagars', 'data_emissao_nfe')) {
            Schema::table('conta_pagars', function (Blueprint $table) {
                $table->dropColumn('data_emissao_nfe');
            });
        }
    }
};
