<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Somente configuração global do catálogo; nenhum dado legado é alterado.
        if (Schema::hasTable('ui_theme_system_settings')) {
            foreach (['id', 'default_theme_slug', 'created_at', 'updated_at'] as $column) {
                if (!Schema::hasColumn('ui_theme_system_settings', $column)) {
                    throw new \RuntimeException('Tabela ui_theme_system_settings existente sem coluna ' . $column);
                }
            }
            return;
        }

        Schema::create('ui_theme_system_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('default_theme_slug', 80);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Rollback conservador: não apagar a escolha administrativa.
    }
};
