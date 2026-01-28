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
        Schema::create('apontamentos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('apontamentos_empresa_id_foreign');
            $table->unsignedInteger('produto_id')->nullable()->index('apontamentos_produto_id_foreign');
            $table->unsignedInteger('usuario_id')->nullable()->index('apontamentos_usuario_id_foreign');
            $table->decimal('quantidade', 10, 3);
            $table->timestamp('data_registro')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apontamentos');
    }
};
