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
        Schema::create('preco_produto_ifoods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_ifood', 50);
            $table->unsignedInteger('produto_ifood_id')->index('preco_produto_ifoods_produto_ifood_id_foreign');
            $table->decimal('valor', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preco_produto_ifoods');
    }
};
