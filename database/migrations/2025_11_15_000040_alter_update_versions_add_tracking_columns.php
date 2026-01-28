<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('update_versions', function (Blueprint $table) {
            $table->string('mode')->default('stable')->after('status');
            $table->string('provider')->nullable()->after('mode');
            $table->string('composer_action')->default('none')->after('metadata');
            $table->boolean('is_downgrade')->default(false)->after('composer_action');
            $table->text('observations')->nullable()->after('is_downgrade');
        });
    }

    public function down(): void
    {
        Schema::table('update_versions', function (Blueprint $table) {
            $table->dropColumn(['mode', 'provider', 'composer_action', 'is_downgrade', 'observations']);
        });
    }
};
