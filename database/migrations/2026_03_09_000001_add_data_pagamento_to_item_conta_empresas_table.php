<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('item_conta_empresas', 'data_pagamento')) {
            Schema::table('item_conta_empresas', function (Blueprint $table) {
                $table->date('data_pagamento')->nullable()->after('valor');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('item_conta_empresas', 'data_pagamento')) {
            Schema::table('item_conta_empresas', function (Blueprint $table) {
                $table->dropColumn('data_pagamento');
            });
        }
    }
};
