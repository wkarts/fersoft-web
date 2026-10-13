<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Preserva os dados de instalações que executaram a migration
        // agrupada anterior antes da separação em três arquivos.
        if (Schema::hasTable('migrations') && DB::table('migrations')
            ->where('migration', '2026_10_12_180500_create_login_presentation_tables')
            ->exists()) {
            return;
        }

        // Reverte somente a tabela pertencente a esta migration.
        Schema::dropIfExists('login_presentation_contents');
    }
};
