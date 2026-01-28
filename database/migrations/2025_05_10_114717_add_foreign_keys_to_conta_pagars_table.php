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
        Schema::table('conta_pagars', function (Blueprint $table) {
            $table->foreign(['categoria_id'])->references(['id'])->on('categoria_contas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['compra_id'])->references(['id'])->on('compras')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['filial_id'])->references(['id'])->on('filials')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conta_pagars', function (Blueprint $table) {
            $table->dropForeign('conta_pagars_categoria_id_foreign');
            $table->dropForeign('conta_pagars_compra_id_foreign');
            $table->dropForeign('conta_pagars_empresa_id_foreign');
            $table->dropForeign('conta_pagars_filial_id_foreign');
        });
    }
};
