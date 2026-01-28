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
        Schema::create('pedido_deletes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('pedido_deletes_empresa_id_foreign');
            $table->string('produto', 100);
            $table->integer('pedido_id');
            $table->decimal('valor', 10);
            $table->string('data_insercao', 20);
            $table->decimal('quantidade', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_deletes');
    }
};
