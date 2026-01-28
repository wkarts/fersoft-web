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
        Schema::table('funcionario_eventos', function (Blueprint $table) {
            $table->foreign(['evento_id'])->references(['id'])->on('evento_salarios')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['funcionario_id'])->references(['id'])->on('funcionarios')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('funcionario_eventos', function (Blueprint $table) {
            $table->dropForeign('funcionario_eventos_evento_id_foreign');
            $table->dropForeign('funcionario_eventos_funcionario_id_foreign');
        });
    }
};
