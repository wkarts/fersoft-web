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
        Schema::table('nfse_servicos', function (Blueprint $table) {
            $table->foreign(['nfse_id'])->references(['id'])->on('nfses')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['servico_id'])->references(['id'])->on('servicos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nfse_servicos', function (Blueprint $table) {
            $table->dropForeign('nfse_servicos_nfse_id_foreign');
            $table->dropForeign('nfse_servicos_servico_id_foreign');
        });
    }
};
