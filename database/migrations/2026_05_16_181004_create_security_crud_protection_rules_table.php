<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('security_crud_protection_rules')) return;

        Schema::create('security_crud_protection_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable()->index();
            $table->unsignedBigInteger('security_crud_resource_id')->index();
            $table->string('action', 40)->index();
            $table->string('protection_type', 60)->default('none')->index();
            $table->boolean('requires_authorizer')->default(false);
            $table->boolean('allow_self_authorization')->default(false);
            $table->boolean('bypass_super_admin')->default(true);
            $table->boolean('bypass_company_admin')->default(false);
            $table->boolean('enabled')->default(true)->index();
            $table->text('message')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['empresa_id', 'security_crud_resource_id', 'action'], 'sec_crud_rule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_crud_protection_rules');
    }
};
