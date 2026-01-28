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
        Schema::table('boletos', function (Blueprint $table) {
            $table->foreign(['banco_id'])->references(['id'])->on('conta_bancarias')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['conta_id'])->references(['id'])->on('conta_recebers')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boletos', function (Blueprint $table) {
            $table->dropForeign('boletos_banco_id_foreign');
            $table->dropForeign('boletos_conta_id_foreign');
        });
    }
};
