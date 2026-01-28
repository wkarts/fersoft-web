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
        Schema::table('item_agendamentos', function (Blueprint $table) {
            $table->foreign(['agendamento_id'])->references(['id'])->on('agendamentos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['servico_id'])->references(['id'])->on('servicos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_agendamentos', function (Blueprint $table) {
            $table->dropForeign('item_agendamentos_agendamento_id_foreign');
            $table->dropForeign('item_agendamentos_servico_id_foreign');
        });
    }
};
