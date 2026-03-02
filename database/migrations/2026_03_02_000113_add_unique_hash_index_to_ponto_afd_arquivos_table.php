<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ponto_afd_arquivos')) {
            Schema::table('ponto_afd_arquivos', function (Blueprint $table) {
                $table->unique(['empresa_id', 'hash_arquivo'], 'ponto_afd_arquivos_empresa_hash_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ponto_afd_arquivos')) {
            Schema::table('ponto_afd_arquivos', function (Blueprint $table) {
                $table->dropUnique('ponto_afd_arquivos_empresa_hash_unique');
            });
        }
    }
};
