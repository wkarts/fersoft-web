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
        Schema::table('acesso_logs', function (Blueprint $table) {
            $table->foreign(['usuario_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acesso_logs', function (Blueprint $table) {
            $table->dropForeign('acesso_logs_usuario_id_foreign');
        });
    }
};
