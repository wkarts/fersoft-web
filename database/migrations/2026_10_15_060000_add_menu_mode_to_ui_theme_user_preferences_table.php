<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ui_theme_user_preferences') && !Schema::hasColumn('ui_theme_user_preferences', 'menu_mode')) {
            Schema::table('ui_theme_user_preferences', function (Blueprint $table) {
                $table->string('menu_mode', 16)->default('follow');
            });
        }
    }
    public function down(): void
    {
        // Retain user preferences during code rollback. Classic ignores these optional fields.
    }
};
