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
        Schema::create('token_cliente_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('token', 200);
            $table->string('user_id', 40);
            $table->unsignedInteger('cliente_id')->nullable()->index('token_cliente_deliveries_cliente_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_cliente_deliveries');
    }
};
