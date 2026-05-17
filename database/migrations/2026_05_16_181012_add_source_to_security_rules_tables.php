<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('security_crud_permissions')) {
            Schema::table('security_crud_permissions', function (Blueprint $table) {
                if (!Schema::hasColumn('security_crud_permissions', 'source')) {
                    $table->string('source', 30)->default('manual')->index()->after('enabled');
                }
                if (!Schema::hasColumn('security_crud_permissions', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->index()->after('source');
                }
                if (!Schema::hasColumn('security_crud_permissions', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->index()->after('created_by');
                }
            });
        }

        if (Schema::hasTable('security_crud_protection_rules')) {
            Schema::table('security_crud_protection_rules', function (Blueprint $table) {
                if (!Schema::hasColumn('security_crud_protection_rules', 'source')) {
                    $table->string('source', 30)->default('manual')->index()->after('message');
                }
                if (!Schema::hasColumn('security_crud_protection_rules', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->index()->after('source');
                }
                if (!Schema::hasColumn('security_crud_protection_rules', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->index()->after('created_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('security_crud_permissions')) {
            Schema::table('security_crud_permissions', function (Blueprint $table) {
                foreach (['updated_by', 'created_by', 'source'] as $column) {
                    if (Schema::hasColumn('security_crud_permissions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('security_crud_protection_rules')) {
            Schema::table('security_crud_protection_rules', function (Blueprint $table) {
                foreach (['updated_by', 'created_by', 'source'] as $column) {
                    if (Schema::hasColumn('security_crud_protection_rules', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
