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
        Schema::create('custos_operacionais', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->nullable()->index('custos_operacionais_empresa_id_foreign');
            $table->unsignedInteger('usuario_id')->nullable()->index('custos_operacionais_usuario_id_foreign');
            $table->unsignedInteger('veiculo_id')->nullable()->index('custos_operacionais_veiculo_id_foreign');
            $table->unsignedInteger('responsavel_id')->nullable()->index('custos_operacionais_responsavel_id_foreign');
            $table->enum('tipo_custo', ['Abastecimento', 'Multa', 'Seguro', 'Outros']);
            $table->decimal('valor', 10);
            $table->text('descricao')->nullable();
            $table->date('data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custos_operacionais');
    }
};
