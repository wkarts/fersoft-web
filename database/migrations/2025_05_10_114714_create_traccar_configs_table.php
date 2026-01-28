<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('traccar_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id')->nullable()->index('traccar_configs_empresa_id_foreign');
            $table->unsignedInteger('usuario_id')->nullable()->index('traccar_configs_usuario_id_foreign');
            $table->string('base_url');
            $table->string('socket_url');
            $table->string('mail_user_name');
            $table->string('password');
            $table->string('token_traccar')->nullable();
            $table->string('default_map')->nullable();
            $table->string('token_google_maps')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traccar_configs');
    }
};
