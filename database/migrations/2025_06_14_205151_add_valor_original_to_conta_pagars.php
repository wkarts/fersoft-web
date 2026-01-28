<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddValorOriginalToContaPagars extends Migration
{
    public function up()
    {
        Schema::table('conta_pagars', function (Blueprint $table) {
            $table->decimal('valor_original', 16, 7)->nullable()->after('valor_integral');
        });
    }

    public function down()
    {
        Schema::table('conta_pagars', function (Blueprint $table) {
            $table->dropColumn('valor_original');
        });
    }
}
