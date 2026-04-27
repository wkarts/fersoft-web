<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa a migration.
     */
    public function up(): void
    {
        Schema::table('fornecedors', function (Blueprint $table) {
            if (!Schema::hasColumn('fornecedors', 'banco')) {
                $table->string('banco', 50)->nullable()->default('')->after('updated_at');
            }

            if (!Schema::hasColumn('fornecedors', 'agencia')) {
                $table->string('agencia', 20)->nullable()->default('')->after('banco');
            }

            if (!Schema::hasColumn('fornecedors', 'conta')) {
                $table->string('conta', 20)->nullable()->default('')->after('agencia');
            }
        });
    }

    /**
     * Reverte a migration.
     */
    public function down(): void
    {
        Schema::table('fornecedors', function (Blueprint $table) {
            if (Schema::hasColumn('fornecedors', 'conta')) {
                $table->dropColumn('conta');
            }

            if (Schema::hasColumn('fornecedors', 'agencia')) {
                $table->dropColumn('agencia');
            }

            if (Schema::hasColumn('fornecedors', 'banco')) {
                $table->dropColumn('banco');
            }
        });
    }
};
