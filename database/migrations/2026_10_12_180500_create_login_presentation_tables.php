<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('login_presentations')) {
            Schema::create('login_presentations', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 110)->unique();
                $table->string('layout', 40)->default('split');
                $table->string('font', 40)->default('poppins');
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(false)->index();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('login_presentation_contents')) {
            Schema::create('login_presentation_contents', function (Blueprint $table) {
                $table->id();
                // Pode ser global: sem vínculo obrigatório com um modelo específico.
                $table->unsignedBigInteger('presentation_id')->nullable()->index();
                $table->string('type', 20);
                $table->string('title', 200)->nullable();
                $table->text('body');
                $table->string('image_path', 255)->nullable();
                $table->string('link_url', 2048)->nullable();
                $table->string('status', 20)->default('draft')->index();
                $table->integer('sort_order')->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['type', 'status', 'starts_at', 'ends_at'], 'login_content_schedule_idx');
            });
        }

        if (!Schema::hasTable('login_presentation_assets')) {
            Schema::create('login_presentation_assets', function (Blueprint $table) {
                $table->id();
                $table->string('asset_key', 40)->index();
                $table->string('path', 255);
                $table->string('mime_type', 60);
                $table->string('sha256', 64);
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->unsignedBigInteger('size_bytes');
                $table->boolean('is_active')->default(false)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['asset_key', 'is_active'], 'login_asset_active_idx');
            });
        }
    }

    // Conservador: reversão jamais remove tabelas ou conteúdo institucional.
    public function down(): void
    {
    }
};
