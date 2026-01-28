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
        Schema::create('manutencoes', function (Blueprint $table) {
            $table->increments('manutencao_id');
            $table->unsignedInteger('empresa_id')->nullable()->index('manutencoes_empresa_id_foreign');
            $table->unsignedInteger('usuario_id')->nullable()->index('manutencoes_usuario_id_foreign');
            $table->unsignedInteger('veiculo_id')->nullable()->index('manutencoes_veiculo_id_foreign');
            $table->unsignedInteger('responsavel_id')->nullable()->index('manutencoes_responsavel_id_foreign');
            $table->string('descricao');
            $table->date('data_manutencao');
            $table->decimal('custo', 10)->default(0);
            $table->enum('prioridade', ['Baixa', 'Média', 'Alta'])->default('Média');
            $table->json('checklist')->nullable();
            $table->enum('status', ['Planejada', 'Concluída'])->default('Planejada');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manutencoes');
    }
};
