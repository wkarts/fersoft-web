<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('empresa_security_settings')) {
            Schema::create('empresa_security_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable()->index();
                $table->boolean('platform_enabled')->default(true);
                $table->boolean('tenant_enabled')->default(false);
                $table->boolean('enforcement_enabled')->default(false);
                $table->boolean('legacy_password_disabled')->default(false);
                $table->boolean('google_auth_required')->default(false);
                $table->boolean('audit_sensitive_export_enabled')->default(false);
                $table->boolean('restore_from_audit_enabled')->default(false);
                $table->timestamp('setup_completed_at')->nullable();
                $table->timestamp('enabled_at')->nullable();
                $table->unsignedBigInteger('enabled_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->unique('empresa_id', 'empresa_security_settings_empresa_id_unique');
            });
            return;
        }

        Schema::table('empresa_security_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('empresa_security_settings', 'platform_enabled')) $table->boolean('platform_enabled')->default(true)->after('empresa_id');
            if (!Schema::hasColumn('empresa_security_settings', 'tenant_enabled')) $table->boolean('tenant_enabled')->default(false)->after('platform_enabled');
            if (!Schema::hasColumn('empresa_security_settings', 'enforcement_enabled')) $table->boolean('enforcement_enabled')->default(false)->after('tenant_enabled');
            if (!Schema::hasColumn('empresa_security_settings', 'legacy_password_disabled')) $table->boolean('legacy_password_disabled')->default(false)->after('enforcement_enabled');
            if (!Schema::hasColumn('empresa_security_settings', 'google_auth_required')) $table->boolean('google_auth_required')->default(false)->after('legacy_password_disabled');
            if (!Schema::hasColumn('empresa_security_settings', 'audit_sensitive_export_enabled')) $table->boolean('audit_sensitive_export_enabled')->default(false)->after('google_auth_required');
            if (!Schema::hasColumn('empresa_security_settings', 'restore_from_audit_enabled')) $table->boolean('restore_from_audit_enabled')->default(false)->after('audit_sensitive_export_enabled');
            if (!Schema::hasColumn('empresa_security_settings', 'setup_completed_at')) $table->timestamp('setup_completed_at')->nullable()->after('restore_from_audit_enabled');
            if (!Schema::hasColumn('empresa_security_settings', 'enabled_at')) $table->timestamp('enabled_at')->nullable()->after('setup_completed_at');
            if (!Schema::hasColumn('empresa_security_settings', 'enabled_by')) $table->unsignedBigInteger('enabled_by')->nullable()->index()->after('enabled_at');
            if (!Schema::hasColumn('empresa_security_settings', 'deleted_at')) $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_security_settings');
    }
};
