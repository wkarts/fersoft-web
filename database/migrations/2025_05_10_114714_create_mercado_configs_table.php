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
        Schema::create('mercado_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('mercado_configs_empresa_id_foreign');
            $table->string('email', 50);
            $table->string('funcionamento', 100);
            $table->string('descricao', 200);
            $table->integer('total_de_produtos');
            $table->integer('total_de_clientes');
            $table->integer('total_de_funcionarios');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mercado_configs');
    }
};
