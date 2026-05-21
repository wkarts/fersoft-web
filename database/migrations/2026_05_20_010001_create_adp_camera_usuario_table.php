<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('adp_camera_usuario')) {
            Schema::create('adp_camera_usuario', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('empresa_id')->index();
                $table->unsignedBigInteger('adp_camera_id')->index();
                $table->unsignedInteger('usuario_id')->index();
                $table->timestamps();
                $table->unique(['empresa_id', 'adp_camera_id', 'usuario_id'], 'adp_camera_usuario_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('adp_camera_usuario');
    }
};
