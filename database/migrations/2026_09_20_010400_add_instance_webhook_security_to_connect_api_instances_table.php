<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('connect_api_instances')) {
            return;
        }

        $addWebhookToken = !Schema::hasColumn('connect_api_instances', 'webhook_token');
        $addWebhookTokenHash = !Schema::hasColumn('connect_api_instances', 'webhook_token_hash');
        $addWebhookConfiguredAt = !Schema::hasColumn('connect_api_instances', 'webhook_configured_at');
        $addWebhookLastReceivedAt = !Schema::hasColumn('connect_api_instances', 'webhook_last_received_at');

        if ($addWebhookToken || $addWebhookTokenHash || $addWebhookConfiguredAt || $addWebhookLastReceivedAt) {
            Schema::table('connect_api_instances', function (Blueprint $table) use (
                $addWebhookToken,
                $addWebhookTokenHash,
                $addWebhookConfiguredAt,
                $addWebhookLastReceivedAt
            ) {
                if ($addWebhookToken) {
                    $table->text('webhook_token')->nullable()->after('instance_token');
                }

                if ($addWebhookTokenHash) {
                    $table->string('webhook_token_hash', 64)->nullable()->after('webhook_token');
                }

                if ($addWebhookConfiguredAt) {
                    $table->timestamp('webhook_configured_at')->nullable()->after('webhook_token_hash');
                }

                if ($addWebhookLastReceivedAt) {
                    $table->timestamp('webhook_last_received_at')->nullable()->after('webhook_configured_at');
                }
            });
        }

        if ($addWebhookTokenHash) {
            Schema::table('connect_api_instances', function (Blueprint $table) {
                $table->unique('webhook_token_hash', 'connect_api_instances_webhook_token_hash_unique');
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable('connect_api_instances')) {
            return;
        }

        if (Schema::hasColumn('connect_api_instances', 'webhook_token_hash')) {
            Schema::table('connect_api_instances', function (Blueprint $table) {
                $table->dropUnique('connect_api_instances_webhook_token_hash_unique');
            });
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('connect_api_instances', 'webhook_last_received_at') ? 'webhook_last_received_at' : null,
            Schema::hasColumn('connect_api_instances', 'webhook_configured_at') ? 'webhook_configured_at' : null,
            Schema::hasColumn('connect_api_instances', 'webhook_token_hash') ? 'webhook_token_hash' : null,
            Schema::hasColumn('connect_api_instances', 'webhook_token') ? 'webhook_token' : null,
        ]));

        if ($columns !== []) {
            Schema::table('connect_api_instances', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
