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
        Schema::dropIfExists('login_presentations');
    }
};
