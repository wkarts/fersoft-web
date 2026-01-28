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
        Schema::create('produto_os', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produto_id')->index('produto_os_produto_id_foreign');
            $table->unsignedInteger('ordem_servico_id')->index('produto_os_ordem_servico_id_foreign');
            $table->integer('quantidade');
            $table->decimal('valor_unitario', 16, 7);
            $table->decimal('sub_total', 16, 7);
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produto_os');
    }
};
