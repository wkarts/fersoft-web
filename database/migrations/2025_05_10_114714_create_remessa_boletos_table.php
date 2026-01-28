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
        Schema::create('remessa_boletos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('remessa_id')->index('remessa_boletos_remessa_id_foreign');
            $table->unsignedInteger('boleto_id')->index('remessa_boletos_boleto_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remessa_boletos');
    }
};
