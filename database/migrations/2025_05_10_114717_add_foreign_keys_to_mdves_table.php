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
        Schema::table('mdves', function (Blueprint $table) {
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['filial_id'])->references(['id'])->on('filials')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['veiculo_reboque2_id'])->references(['id'])->on('veiculos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['veiculo_reboque3_id'])->references(['id'])->on('veiculos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['veiculo_reboque_id'])->references(['id'])->on('veiculos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['veiculo_tracao_id'])->references(['id'])->on('veiculos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mdves', function (Blueprint $table) {
            $table->dropForeign('mdves_empresa_id_foreign');
            $table->dropForeign('mdves_filial_id_foreign');
            $table->dropForeign('mdves_veiculo_reboque2_id_foreign');
            $table->dropForeign('mdves_veiculo_reboque3_id_foreign');
            $table->dropForeign('mdves_veiculo_reboque_id_foreign');
            $table->dropForeign('mdves_veiculo_tracao_id_foreign');
        });
    }
};
