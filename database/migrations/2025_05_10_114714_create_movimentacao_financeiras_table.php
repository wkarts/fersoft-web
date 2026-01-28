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
        Schema::create('movimentacao_financeiras', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('movimentacao_financeiras_empresa_id_foreign');
            $table->unsignedInteger('conta_id')->index('movimentacao_financeiras_conta_id_foreign');
            $table->string('tabela', 50);
            $table->boolean('status');
            $table->decimal('valor', 20, 7);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimentacao_financeiras');
    }
};
