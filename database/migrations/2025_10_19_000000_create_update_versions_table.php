<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('update_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('update_versions');
    }
};
