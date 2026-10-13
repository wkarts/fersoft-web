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
        Schema::dropIfExists('login_presentation_assets');
    }
};
