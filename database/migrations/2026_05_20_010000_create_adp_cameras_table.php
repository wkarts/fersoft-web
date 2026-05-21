<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('adp_cameras')) {
            Schema::create('adp_cameras', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('empresa_id')->index();
                $table->unsignedBigInteger('integrador_config_id')->nullable()->index();
                $table->string('camera_uuid', 255);
                $table->string('descricao', 255)->nullable();
                $table->string('name', 255)->nullable();
                $table->string('model', 255)->nullable();
                $table->string('driver', 255)->nullable();
                $table->string('protocol', 100)->nullable();
                $table->string('host', 255)->nullable();
                $table->string('port', 50)->nullable();
                $table->text('stream_url')->nullable();
                $table->text('snapshot_url')->nullable();
                $table->boolean('supports_stream')->default(false);
                $table->boolean('supports_snapshot')->default(false);
                $table->string('status', 80)->nullable();
                $table->timestamp('ultimo_status_em')->nullable();
                $table->boolean('ativo')->default(true)->index();
                $table->json('metadata_json')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->unique(['empresa_id', 'camera_uuid'], 'adp_cameras_empresa_uuid_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('adp_cameras');
    }
};
