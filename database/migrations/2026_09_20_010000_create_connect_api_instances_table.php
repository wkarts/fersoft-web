<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('connect_api_instances')) {
            return;
        }

        Schema::create('connect_api_instances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('usuario_id')->nullable();
            $table->unsignedInteger('filial_id')->nullable();
            $table->string('instance_name', 100);
            $table->string('remote_instance_id', 191)->nullable();
            $table->text('instance_token')->nullable();
            $table->string('connected_number', 30)->nullable();
            $table->string('connected_name', 191)->nullable();
            $table->string('connection_status', 40)->default('not_provisioned');
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamp('paired_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamp('last_status_at')->nullable();
            $table->string('last_error_code', 100)->nullable();
            $table->text('last_error_message')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('empresa_id', 'connect_api_instances_empresa_unique');
            $table->unique('instance_name', 'connect_api_instances_name_unique');
            $table->index('connection_status', 'connect_api_instances_status_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_api_instances');
    }
};
