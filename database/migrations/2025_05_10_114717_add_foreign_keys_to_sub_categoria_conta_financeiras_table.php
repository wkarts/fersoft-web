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
        Schema::table('sub_categoria_conta_financeiras', function (Blueprint $table) {
            $table->foreign(['categoria_id'])->references(['id'])->on('categoria_conta_financeiras')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_categoria_conta_financeiras', function (Blueprint $table) {
            $table->dropForeign('sub_categoria_conta_financeiras_categoria_id_foreign');
        });
    }
};
