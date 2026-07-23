<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('funcionarios') && !Schema::hasColumn('funcionarios', 'cep')) {
            Schema::table('funcionarios', function (Blueprint $table) {
                $table->string('cep', 8)->nullable()->after('bairro');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('funcionarios') && Schema::hasColumn('funcionarios', 'cep')) {
            Schema::table('funcionarios', function (Blueprint $table) {
                $table->dropColumn('cep');
            });
        }
    }
};
