<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mdfe_pagamentos') && Schema::hasTable('mdves')) {
            Schema::table('mdfe_pagamentos', function (Blueprint $table) {
                $table->foreign(['mdfe_id'], 'mdfe_pagamentos_mdfe_id_foreign')
                    ->references(['id'])
                    ->on('mdves')
                    ->onUpdate('no action')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mdfe_pagamentos')) {
            Schema::table('mdfe_pagamentos', function (Blueprint $table) {
                $table->dropForeign('mdfe_pagamentos_mdfe_id_foreign');
            });
        }
    }
};
