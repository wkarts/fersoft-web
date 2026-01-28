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
        Schema::create('remessa_referencia_nves', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('remessa_id')->index('remessa_referencia_nves_remessa_id_foreign');
            $table->string('chave', 44);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remessa_referencia_nves');
    }
};
