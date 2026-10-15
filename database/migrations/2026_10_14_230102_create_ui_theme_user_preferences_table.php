<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migração exclusivamente aditiva, sem DROP ou alteração em tabelas legadas.
        if (Schema::hasTable('ui_theme_user_preferences')) {
            foreach (['id', 'company_id', 'user_id', 'theme_slug', 'color_mode', 'density'] as $column) {
                if (!Schema::hasColumn('ui_theme_user_preferences', $column)) {
                    throw new \RuntimeException('Tabela ui_theme_user_preferences existente sem coluna ' . $column);
                }
            }
            return;
        }

        Schema::create('ui_theme_user_preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('user_id');
            $table->string('theme_slug', 80)->default('fer-light');
            $table->string('color_mode', 16)->default('follow');
            $table->string('density', 20)->default('comfortable');
            $table->timestamps();
            $table->unique(['company_id', 'user_id'], 'ui_theme_user_company_unique');
        });
    }

    public function down(): void
    {
        // Rollback intencionalmente conservador: não apagar temas/preferências.
    }
};
