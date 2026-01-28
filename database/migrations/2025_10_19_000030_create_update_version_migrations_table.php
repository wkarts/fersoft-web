<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('update_version_migrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('update_version_id')->constrained('update_versions')->cascadeOnDelete();
            $table->string('migration');
            $table->enum('direction', ['up', 'down'])->default('up');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('update_version_migrations');
    }
};
