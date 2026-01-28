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
        Schema::create('remessa_nfe_faturas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('remessa_id')->index('remessa_nfe_faturas_remessa_id_foreign');
            $table->string('tipo_pagamento', 30);
            $table->decimal('valor', 16, 7);
            $table->date('data_vencimento');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remessa_nfe_faturas');
    }
};
