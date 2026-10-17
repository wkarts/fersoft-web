<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ui_pos_theme_presets')) {
            foreach (['id', 'slug', 'name', 'description', 'colors', 'status', 'created_by', 'published_at', 'created_at', 'updated_at'] as $column) {
                if (!Schema::hasColumn('ui_pos_theme_presets', $column)) {
                    throw new \RuntimeException('Tabela ui_pos_theme_presets existente sem coluna ' . $column);
                }
            }
            return;
        }

        Schema::create('ui_pos_theme_presets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->string('description', 400)->nullable();
            $table->json('colors');
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Catálogo aditivo: rollback de código não descarta temas criados.
    }
};
