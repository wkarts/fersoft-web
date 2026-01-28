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
        Schema::table('movimentacao_financeiras', function (Blueprint $table) {
            $table->foreign(['conta_id'])->references(['id'])->on('conta_financeiras')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimentacao_financeiras', function (Blueprint $table) {
            $table->dropForeign('movimentacao_financeiras_conta_id_foreign');
            $table->dropForeign('movimentacao_financeiras_empresa_id_foreign');
        });
    }
};
