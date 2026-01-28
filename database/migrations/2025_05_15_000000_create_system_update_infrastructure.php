<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('system_updates', function (Blueprint $table) {
            if (!Schema::hasColumn('system_updates', 'mode')) {
                $table->string('mode', 50)->default('full_release');
            }
            if (!Schema::hasColumn('system_updates', 'target_reference')) {
                $table->string('target_reference')->nullable();
            }
            if (!Schema::hasColumn('system_updates', 'status')) {
                $table->string('status', 30)->default('pending');
            }
            if (!Schema::hasColumn('system_updates', 'flags')) {
                $table->json('flags')->nullable();
            }
            if (!Schema::hasColumn('system_updates', 'dry_run')) {
                $table->boolean('dry_run')->default(false);
            }
            if (!Schema::hasColumn('system_updates', 'backup_code_path')) {
                $table->string('backup_code_path')->nullable();
            }
            if (!Schema::hasColumn('system_updates', 'backup_db_path')) {
                $table->string('backup_db_path')->nullable();
            }
            if (!Schema::hasColumn('system_updates', 'initiated_by')) {
                $table->string('initiated_by')->nullable();
            }
            if (!Schema::hasColumn('system_updates', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('system_updates', 'lock_key')) {
                $table->string('lock_key')->nullable();
            }
            if (!Schema::hasColumn('system_updates', 'started_at')) {
                $table->timestamp('started_at')->nullable();
            }
            if (!Schema::hasColumn('system_updates', 'finished_at')) {
                $table->timestamp('finished_at')->nullable();
            }
            if (!Schema::hasColumn('system_updates', 'rollback_reference')) {
                $table->string('rollback_reference')->nullable();
            }
        });

        Schema::create('system_update_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('system_update_id');
            $table->string('path');
            $table->string('hash_before', 128)->nullable();
            $table->string('hash_after', 128)->nullable();
            $table->string('backup_path')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->foreign('system_update_id')->references('id')->on('system_updates')->cascadeOnDelete();
            $table->index(['system_update_id', 'path']);
        });

        Schema::create('system_update_nodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('system_update_id');
            $table->string('hostname');
            $table->string('status', 30)->default('pending');
            $table->text('last_error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->foreign('system_update_id')->references('id')->on('system_updates')->cascadeOnDelete();
            $table->index(['system_update_id', 'hostname']);
        });

        Schema::create('system_update_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('system_update_id');
            $table->string('level', 20)->default('info');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->foreign('system_update_id')->references('id')->on('system_updates')->cascadeOnDelete();
            $table->index(['system_update_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_update_logs');
        Schema::dropIfExists('system_update_nodes');
        Schema::dropIfExists('system_update_files');

        Schema::table('system_updates', function (Blueprint $table) {
            foreach ([
                'mode', 'target_reference', 'status', 'flags', 'dry_run', 'backup_code_path', 'backup_db_path',
                'initiated_by', 'notes', 'lock_key', 'started_at', 'finished_at', 'rollback_reference',
            ] as $column) {
                if (Schema::hasColumn('system_updates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('system_settings');
    }
};
