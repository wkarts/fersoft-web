<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('security_authorizer_resources')) return;

        Schema::create('security_authorizer_resources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable()->index();
            $table->unsignedBigInteger('security_authorizer_id')->index();
            $table->unsignedBigInteger('security_crud_resource_id')->index();
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_restore')->default(false);
            $table->boolean('can_export')->default(false);
            $table->boolean('can_print')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['security_authorizer_id', 'security_crud_resource_id'], 'sec_authorizer_resource_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_authorizer_resources');
    }
};
