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
        Schema::table('atividade_servicos', function (Blueprint $table) {
            $table->foreign(['atividade_id'])->references(['id'])->on('atividade_eventos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['servico_id'])->references(['id'])->on('servicos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atividade_servicos', function (Blueprint $table) {
            $table->dropForeign('atividade_servicos_atividade_id_foreign');
            $table->dropForeign('atividade_servicos_servico_id_foreign');
        });
    }
};
