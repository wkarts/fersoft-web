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
        Schema::create('banner_topos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('banner_topos_empresa_id_foreign');
            $table->string('path', 100);
            $table->string('titulo', 20);
            $table->string('descricao', 100);
            $table->unsignedInteger('produto_delivery_id')->nullable()->index('banner_topos_produto_delivery_id_foreign');
            $table->unsignedInteger('pack_id')->nullable()->index('banner_topos_pack_id_foreign');
            $table->boolean('ativo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banner_topos');
    }
};
