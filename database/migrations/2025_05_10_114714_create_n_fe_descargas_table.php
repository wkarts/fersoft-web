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
        Schema::create('n_fe_descargas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('info_id')->index('n_fe_descargas_info_id_foreign');
            $table->string('chave', 44);
            $table->string('seg_cod_barras', 44);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('n_fe_descargas');
    }
};
