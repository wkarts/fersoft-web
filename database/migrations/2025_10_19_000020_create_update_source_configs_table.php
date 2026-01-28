<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('update_source_configs', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('name')->nullable();
            $table->json('credentials')->nullable();
            $table->json('options')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('update_source_configs');
    }
};
