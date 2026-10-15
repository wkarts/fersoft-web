<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migração exclusivamente aditiva, sem DROP ou alteração em tabelas legadas.
        if (Schema::hasTable('ui_theme_company_settings')) {
            foreach (['id', 'company_id', 'default_theme_slug', 'allowed_theme_slugs'] as $column) {
                if (!Schema::hasColumn('ui_theme_company_settings', $column)) {
                    throw new \RuntimeException('Tabela ui_theme_company_settings existente sem coluna ' . $column);
                }
            }
            return;
        }

        Schema::create('ui_theme_company_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->unique();
            $table->string('default_theme_slug', 80)->nullable();
            $table->json('allowed_theme_slugs')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Rollback intencionalmente conservador: não apagar temas/preferências.
    }
};
