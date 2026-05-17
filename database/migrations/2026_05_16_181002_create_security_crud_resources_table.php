<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('security_crud_resources')) {
            Schema::create('security_crud_resources', function (Blueprint $table) {
                $table->id();
                $table->string('module')->default('Geral')->index();
                $table->string('technical_name')->index();
                $table->string('display_name')->index();
                $table->string('plural_display_name')->nullable();
                $table->text('description')->nullable();
                $table->string('model_class')->unique();
                $table->string('controller_class')->nullable()->index();
                $table->string('route_prefix')->nullable()->index();
                $table->string('table_name')->nullable()->index();
                $table->string('icon')->nullable();
                $table->boolean('sensitive')->default(false)->index();
                $table->boolean('tenant_visible')->default(true)->index();
                $table->boolean('super_admin_only')->default(false)->index();
                $table->boolean('base_model_detected')->default(false);
                $table->boolean('base_controller_detected')->default(false);
                $table->string('source')->default('auto');
                $table->json('actions')->nullable();
                $table->boolean('enabled')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
            return;
        }

        Schema::table('security_crud_resources', function (Blueprint $table) {
            if (!Schema::hasColumn('security_crud_resources', 'actions')) $table->json('actions')->nullable();
            if (!Schema::hasColumn('security_crud_resources', 'deleted_at')) $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_crud_resources');
    }
};
