<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('adp_devices')) return;

        Schema::create('adp_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('empresa_id')->index();
            $table->unsignedBigInteger('integrador_config_id')->nullable()->index();
            $table->string('device_uuid')->index();
            $table->string('device_type', 30)->index();
            $table->string('name')->nullable();
            $table->string('model')->nullable();
            $table->string('driver')->nullable();
            $table->string('protocol')->nullable();
            $table->string('host')->nullable();
            $table->string('port')->nullable();
            $table->integer('baud_rate')->nullable();
            $table->string('status', 30)->nullable();
            $table->boolean('supports_stream')->default(false);
            $table->boolean('supports_snapshot')->default(false);
            $table->json('metadata_json')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->boolean('ativo')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['empresa_id', 'device_uuid'], 'adp_devices_empresa_uuid_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('adp_devices')) return;
        Schema::drop('adp_devices');
    }
};
