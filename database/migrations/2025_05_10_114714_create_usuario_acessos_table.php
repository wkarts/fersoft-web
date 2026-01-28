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
        Schema::create('usuario_acessos', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('status')->default(false);
            $table->unsignedInteger('usuario_id')->index('usuario_acessos_usuario_id_foreign');
            $table->string('hash', 20);
            $table->string('ip_address', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_acessos');
    }
};
