<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clientes') && !Schema::hasColumn('clientes', 'pix')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('pix', 40)->nullable()->after('whatsapp');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('clientes') && Schema::hasColumn('clientes', 'pix')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropColumn('pix');
            });
        }
    }
};
