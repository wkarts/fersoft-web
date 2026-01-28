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
        Schema::create('movimentacoes_veiculos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id')->nullable()->index('movimentacoes_veiculos_empresa_id_foreign');
            $table->unsignedInteger('usuario_id')->nullable()->index('movimentacoes_veiculos_usuario_id_foreign');
            $table->unsignedInteger('veiculo_id')->index('movimentacoes_veiculos_veiculo_id_foreign');
            $table->unsignedInteger('motorista_id')->nullable()->index('movimentacoes_veiculos_motorista_id_foreign');
            $table->unsignedBigInteger('tipo_movimentacao_id')->index('movimentacoes_veiculos_tipo_movimentacao_id_foreign');
            $table->date('data_movimentacao');
            $table->integer('km_saida')->nullable();
            $table->integer('km_chegada')->nullable();
            $table->decimal('custo', 10)->nullable();
            $table->text('observacoes')->nullable();
            $table->enum('status', ['em andamento', 'concluída'])->default('em andamento');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_veiculos');
    }
};
