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
        Schema::table('servico_os', function (Blueprint $table) {
            $table->foreign(['ordem_servico_id'])->references(['id'])->on('ordem_servicos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['servico_id'])->references(['id'])->on('servicos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servico_os', function (Blueprint $table) {
            $table->dropForeign('servico_os_ordem_servico_id_foreign');
            $table->dropForeign('servico_os_servico_id_foreign');
        });
    }
};
