<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('security_authorizers')) return;

        Schema::create('security_authorizers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable()->index();
            $table->unsignedBigInteger('usuario_id')->index();
            $table->boolean('enabled')->default(true)->index();
            $table->boolean('can_authorize_all_resources')->default(false);
            $table->boolean('can_authorize_view')->default(false);
            $table->boolean('can_authorize_create')->default(false);
            $table->boolean('can_authorize_edit')->default(false);
            $table->boolean('can_authorize_delete')->default(false);
            $table->boolean('can_authorize_restore')->default(false);
            $table->boolean('can_authorize_export')->default(false);
            $table->boolean('can_authorize_print')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['empresa_id', 'usuario_id'], 'sec_authorizers_emp_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_authorizers');
    }
};
