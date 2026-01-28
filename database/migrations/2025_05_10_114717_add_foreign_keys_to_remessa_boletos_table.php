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
        Schema::table('remessa_boletos', function (Blueprint $table) {
            $table->foreign(['boleto_id'])->references(['id'])->on('boletos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['remessa_id'])->references(['id'])->on('remessas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remessa_boletos', function (Blueprint $table) {
            $table->dropForeign('remessa_boletos_boleto_id_foreign');
            $table->dropForeign('remessa_boletos_remessa_id_foreign');
        });
    }
};
