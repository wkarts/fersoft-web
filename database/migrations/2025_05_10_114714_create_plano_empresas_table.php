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
        Schema::create('plano_empresas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('plano_empresas_empresa_id_foreign');
            $table->unsignedInteger('plano_id')->index('plano_empresas_plano_id_foreign');
            $table->date('expiracao');
            $table->string('mensagem_alerta')->default('');
            $table->decimal('valor', 10)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plano_empresas');
    }
};
