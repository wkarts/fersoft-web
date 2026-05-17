<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('security_operation_authorizations')) {
            Schema::create('security_operation_authorizations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->nullable();
                $table->unsignedBigInteger('security_crud_resource_id')->nullable();
                $table->unsignedBigInteger('executor_user_id')->nullable();
                $table->unsignedBigInteger('authorizer_user_id')->nullable();
                $table->string('action', 40);
                $table->unsignedBigInteger('record_id')->nullable();
                $table->string('authorization_token_hash')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('used_at')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('empresa_id', 'soa_empresa_idx');
                $table->index('security_crud_resource_id', 'soa_resource_idx');
                $table->index('executor_user_id', 'soa_executor_idx');
                $table->index('authorizer_user_id', 'soa_authorizer_idx');
                $table->index('action', 'soa_action_idx');
                $table->index('record_id', 'soa_record_idx');
                $table->index('expires_at', 'soa_expires_idx');
                $table->index('used_at', 'soa_used_idx');
            });

            return;
        }

        $this->ensureIndex('empresa_id', 'soa_empresa_idx');
        $this->ensureIndex('security_crud_resource_id', 'soa_resource_idx');
        $this->ensureIndex('executor_user_id', 'soa_executor_idx');
        $this->ensureIndex('authorizer_user_id', 'soa_authorizer_idx');
        $this->ensureIndex('action', 'soa_action_idx');
        $this->ensureIndex('record_id', 'soa_record_idx');
        $this->ensureIndex('expires_at', 'soa_expires_idx');
        $this->ensureIndex('used_at', 'soa_used_idx');
    }

    private function ensureIndex(string $column, string $indexName): void
    {
        if (!Schema::hasColumn('security_operation_authorizations', $column)) {
            return;
        }

        try {
            Schema::table('security_operation_authorizations', function (Blueprint $table) use ($column, $indexName) {
                $table->index($column, $indexName);
            });
        } catch (Throwable $e) {
            // Índice já existente ou banco criado parcialmente por tentativa anterior.
            // A migration deve continuar idempotente para ambientes de produção.
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_operation_authorizations');
    }
};
