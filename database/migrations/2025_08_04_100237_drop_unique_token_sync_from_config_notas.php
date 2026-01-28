<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('config_notas', function (Blueprint $table) {
            $table->dropUnique('config_notas_token_sync_unique');
        });
    }

    public function down()
    {
        Schema::table('config_notas', function (Blueprint $table) {
            $table->unique('token_sync', 'config_notas_token_sync_unique');
        });
    }
};
