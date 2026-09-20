<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('automation_rules')) {
            Schema::create('automation_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('empresa_id')->nullable();
                $table->string('name', 191);
                $table->text('description')->nullable();
                $table->string('trigger', 191);
                $table->boolean('enabled')->default(true);
                $table->unsignedInteger('priority')->default(100);
                $table->boolean('stop_on_success')->default(false);
                $table->json('conditions')->nullable();
                $table->json('actions')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['empresa_id', 'trigger', 'enabled'], 'automation_rules_trigger_idx');
            });
        }

        if (!Schema::hasTable('automation_executions')) {
            Schema::create('automation_executions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('automation_rule_id')->nullable();
                $table->unsignedInteger('empresa_id')->nullable();
                $table->string('trigger', 191);
                $table->unsignedBigInteger('connect_api_webhook_event_id')->nullable();
                $table->string('status', 30);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->text('error')->nullable();
                $table->json('context')->nullable();
                $table->timestamps();

                $table->index(['empresa_id', 'trigger'], 'automation_executions_trigger_idx');
            });
        }

        if (!Schema::hasTable('connect_api_template_bindings')) {
            Schema::create('connect_api_template_bindings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('empresa_id')->nullable();
                $table->string('event_key', 191);
                $table->string('template_name', 64);
                $table->string('language', 10)->default('pt_BR');
                $table->unsignedInteger('template_version')->nullable();
                $table->boolean('enabled')->default(true);
                $table->timestamps();

                $table->index(['empresa_id', 'event_key', 'enabled'], 'connect_api_template_binding_idx');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('connect_api_template_bindings');
        Schema::dropIfExists('automation_executions');
        Schema::dropIfExists('automation_rules');
    }
};
