<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('traccar_devices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('uniqueId');
            $table->string('status')->nullable();
            $table->boolean('disabled')->nullable();
            $table->timestamp('lastUpdate')->nullable();
            $table->integer('positionId')->nullable();
            $table->integer('groupId')->nullable();
            $table->string('phone')->nullable();
            $table->string('model')->nullable();
            $table->string('contact')->nullable();
            $table->string('category')->nullable();
            $table->json('attribs')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traccar_devices');
    }
};
