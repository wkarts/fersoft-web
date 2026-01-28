<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pesagens', function (Blueprint $table) {
            $table->unsignedInteger('filial_id')->nullable()->after('empresa_id');
            $table->foreign('filial_id')->references('id')->on('filials')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('pesagens', function (Blueprint $table) {
            $table->dropForeign(['filial_id']);
            $table->dropColumn('filial_id');
        });
    }
};
