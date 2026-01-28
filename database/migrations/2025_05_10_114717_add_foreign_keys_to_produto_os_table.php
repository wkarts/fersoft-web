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
        Schema::table('produto_os', function (Blueprint $table) {
            $table->foreign(['ordem_servico_id'])->references(['id'])->on('ordem_servicos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['produto_id'])->references(['id'])->on('produtos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produto_os', function (Blueprint $table) {
            $table->dropForeign('produto_os_ordem_servico_id_foreign');
            $table->dropForeign('produto_os_produto_id_foreign');
        });
    }
};
