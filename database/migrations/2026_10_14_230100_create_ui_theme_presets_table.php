<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migração exclusivamente aditiva, sem DROP ou alteração em tabelas legadas.
        if (Schema::hasTable('ui_theme_presets')) {
            foreach (['id', 'slug', 'name', 'colors', 'status', 'created_at', 'updated_at'] as $column) {
                if (!Schema::hasColumn('ui_theme_presets', $column)) {
                    throw new \RuntimeException('Tabela ui_theme_presets existente sem coluna ' . $column);
                }
            }
            return;
        }

        Schema::create('ui_theme_presets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->string('description', 400)->nullable();
            $table->json('colors');
            $table->string('sidebar_style', 20)->default('light');
            $table->string('density', 20)->default('comfortable');
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Rollback intencionalmente conservador: não apagar temas/preferências.
    }
};
