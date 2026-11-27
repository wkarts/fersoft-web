<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ui_pos_theme_settings')) {
            foreach (['id', 'default_theme_slug', 'created_at', 'updated_at'] as $column) {
                if (!Schema::hasColumn('ui_pos_theme_settings', $column)) {
                    throw new \RuntimeException('Tabela ui_pos_theme_settings existente sem coluna ' . $column);
                }
            }
            return;
        }

        Schema::create('ui_pos_theme_settings', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('default_theme_slug', 80);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Rollback de código não apaga o padrão escolhido pelo superusuário.
    }
};
