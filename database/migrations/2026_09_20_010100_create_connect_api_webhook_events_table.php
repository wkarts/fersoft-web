<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('connect_api_webhook_events')) {
            return;
        }

        Schema::create('connect_api_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('connect_api_instance_id');
            $table->unsignedInteger('empresa_id');
            $table->string('event_type', 100);
            $table->string('external_event_id', 191)->nullable();
            $table->string('message_id', 191)->nullable();
            $table->string('deduplication_key', 64)->unique();
            $table->json('payload');
            $table->json('normalized_payload')->nullable();
            $table->string('status', 30)->default('received');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status'], 'connect_api_webhook_empresa_status_idx');
            $table->index(['event_type', 'received_at'], 'connect_api_webhook_type_received_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('connect_api_webhook_events');
    }
};
