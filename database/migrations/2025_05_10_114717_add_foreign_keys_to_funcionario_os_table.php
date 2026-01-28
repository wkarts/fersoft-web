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
        Schema::table('funcionario_os', function (Blueprint $table) {
            $table->foreign(['funcionario_id'])->references(['id'])->on('funcionarios')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['ordem_servico_id'])->references(['id'])->on('ordem_servicos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['usuario_id'])->references(['id'])->on('usuarios')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('funcionario_os', function (Blueprint $table) {
            $table->dropForeign('funcionario_os_funcionario_id_foreign');
            $table->dropForeign('funcionario_os_ordem_servico_id_foreign');
            $table->dropForeign('funcionario_os_usuario_id_foreign');
        });
    }
};
