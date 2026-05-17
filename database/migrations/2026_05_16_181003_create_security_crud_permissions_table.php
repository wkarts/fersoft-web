<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('security_crud_permissions')) return;

        Schema::create('security_crud_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable()->index();
            $table->unsignedBigInteger('security_crud_resource_id')->index();
            $table->unsignedBigInteger('perfil_acesso_id')->nullable()->index();
            $table->unsignedBigInteger('usuario_id')->nullable()->index();
            $table->boolean('can_view')->default(true);
            $table->boolean('can_create')->default(true);
            $table->boolean('can_edit')->default(true);
            $table->boolean('can_delete')->default(true);
            $table->boolean('can_restore')->default(false);
            $table->boolean('can_export')->default(true);
            $table->boolean('can_print')->default(true);
            $table->boolean('enabled')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['empresa_id', 'security_crud_resource_id'], 'sec_crud_perm_emp_res_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_crud_permissions');
    }
};
