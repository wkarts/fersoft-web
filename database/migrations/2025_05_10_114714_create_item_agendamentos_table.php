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
        Schema::create('item_agendamentos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('servico_id')->index('item_agendamentos_servico_id_foreign');
            $table->unsignedInteger('agendamento_id')->index('item_agendamentos_agendamento_id_foreign');
            $table->decimal('quantidade', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_agendamentos');
    }
};
