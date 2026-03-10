<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_versions')) {
            return;
        }

        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version', 30)->unique();
            $table->unsignedInteger('version_major')->default(0);
            $table->unsignedInteger('version_minor')->default(0);
            $table->unsignedInteger('version_patch')->default(0);
            $table->string('title', 255)->nullable();
            $table->longText('release_notes_html')->nullable();
            $table->string('release_notes_pdf_path')->nullable();
            $table->string('release_notes_format', 20)->default('none');
            $table->boolean('is_current')->default(false)->index();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->string('build_number', 100)->nullable();
            $table->string('commit_hash', 100)->nullable();
            $table->string('release_channel', 40)->nullable();
            $table->string('author', 255)->nullable();
            $table->text('observations')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['version_major', 'version_minor', 'version_patch']);
            $table->index('installed_at');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('app_versions')) {
            return;
        }

        Schema::dropIfExists('app_versions');
    }
};
