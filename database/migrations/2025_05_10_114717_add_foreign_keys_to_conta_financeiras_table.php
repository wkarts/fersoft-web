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
        Schema::table('conta_financeiras', function (Blueprint $table) {
            $table->foreign(['categoria_id'])->references(['id'])->on('categoria_conta_financeiras')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['sub_categoria_id'])->references(['id'])->on('sub_categoria_conta_financeiras')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conta_financeiras', function (Blueprint $table) {
            $table->dropForeign('conta_financeiras_categoria_id_foreign');
            $table->dropForeign('conta_financeiras_empresa_id_foreign');
            $table->dropForeign('conta_financeiras_sub_categoria_id_foreign');
        });
    }
};
