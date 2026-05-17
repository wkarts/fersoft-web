<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('security_audit_policies')) return;

        Schema::create('security_audit_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable()->index();
            $table->unsignedBigInteger('security_crud_resource_id')->nullable()->index();
            $table->string('model_class')->index();
            $table->boolean('tenant_can_view')->default(true);
            $table->boolean('tenant_can_view_json')->default(false);
            $table->boolean('tenant_can_export_json')->default(false);
            $table->boolean('tenant_can_restore')->default(false);
            $table->boolean('super_admin_only')->default(false)->index();
            $table->json('sanitize_fields')->nullable();
            $table->boolean('enabled')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['empresa_id', 'model_class'], 'sec_audit_policy_emp_model_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_policies');
    }
};
