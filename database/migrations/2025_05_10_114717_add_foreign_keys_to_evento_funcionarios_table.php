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
        Schema::table('evento_funcionarios', function (Blueprint $table) {
            $table->foreign(['evento_id'])->references(['id'])->on('eventos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['funcionario_id'])->references(['id'])->on('funcionarios')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evento_funcionarios', function (Blueprint $table) {
            $table->dropForeign('evento_funcionarios_evento_id_foreign');
            $table->dropForeign('evento_funcionarios_funcionario_id_foreign');
        });
    }
};
