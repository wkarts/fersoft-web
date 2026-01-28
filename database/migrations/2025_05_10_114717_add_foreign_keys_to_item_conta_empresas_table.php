<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('item_conta_empresas', function (Blueprint $table) {
            $table->foreign(['conta_id'])->references(['id'])->on('conta_empresas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_conta_empresas', function (Blueprint $table) {
            $table->dropForeign('item_conta_empresas_conta_id_foreign');
        });
    }
};
