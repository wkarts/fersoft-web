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
        Schema::create('evo_api_instances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('usuario_id')->index('evo_api_instances_usuario_id_foreign');
            $table->unsignedInteger('filial_id')->nullable()->index('evo_api_instances_filial_id_foreign');
            $table->string('name')->unique();
            $table->string('api_key');
            $table->string('instance_id')->nullable();
            $table->boolean('is_blocked')->default(false)->comment('Se true, instância está bloqueada');
            $table->string('base_url')->default('https://connect.hub.argws.com.br');
            $table->string('ddi')->default('55');
            $table->string('ddd')->default('75');
            $table->string('version')->default('V1');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['empresa_id', 'usuario_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evo_api_instances');
    }
};
