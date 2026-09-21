<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (
            Schema::hasTable('conta_pagars')
            && !Schema::hasColumn('conta_pagars', 'contrato_eng_id')
        ) {
            Schema::table('conta_pagars', function (Blueprint $table) {
                $table->unsignedBigInteger('contrato_eng_id')->nullable()->index();
            });
        }
    }

    public function down()
    {
        if (
            Schema::hasTable('conta_pagars')
            && Schema::hasColumn('conta_pagars', 'contrato_eng_id')
        ) {
            Schema::table('conta_pagars', function (Blueprint $table) {
                $table->dropColumn('contrato_eng_id');
            });
        }
    }
};
