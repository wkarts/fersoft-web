<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conta_recebers', function (Blueprint $table) {
            if (!Schema::hasColumn('conta_recebers', 'nf_modelo')) {
                $table->string('nf_modelo', 2)->nullable()->after('venda_id'); // 55 / 65
            }
            if (!Schema::hasColumn('conta_recebers', 'nf_numero')) {
                $table->unsignedInteger('nf_numero')->nullable()->after('nf_modelo');
            }
            if (!Schema::hasColumn('conta_recebers', 'nf_data_emissao')) {
                $table->dateTime('nf_data_emissao')->nullable()->after('nf_numero');
            }
            if (!Schema::hasColumn('conta_recebers', 'nf_chave')) {
                $table->string('nf_chave', 44)->nullable()->after('nf_data_emissao');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conta_recebers', function (Blueprint $table) {
            if (Schema::hasColumn('conta_recebers', 'nf_chave')) {
                $table->dropColumn('nf_chave');
            }
            if (Schema::hasColumn('conta_recebers', 'nf_data_emissao')) {
                $table->dropColumn('nf_data_emissao');
            }
            if (Schema::hasColumn('conta_recebers', 'nf_numero')) {
                $table->dropColumn('nf_numero');
            }
            if (Schema::hasColumn('conta_recebers', 'nf_modelo')) {
                $table->dropColumn('nf_modelo');
            }
        });
    }
};
