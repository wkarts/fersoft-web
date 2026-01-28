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
        Schema::table('produtos', function (Blueprint $table) {
            // Adiciona a coluna produto_referenciado_id (chave estrangeira para a própria tabela produtos)
            $table->unsignedInteger('produto_referenciado_id')->nullable()->after('controla_pesagem');

            // Cria a foreign key: se o produto referenciado for removido, zera este campo
            $table->foreign('produto_referenciado_id')
                ->references('id')
                ->on('produtos')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropForeign(['produto_referenciado_id']);
            $table->dropColumn('produto_referenciado_id');
        });
    }
};
