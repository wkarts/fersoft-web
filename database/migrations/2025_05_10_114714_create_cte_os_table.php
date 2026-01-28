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
        Schema::create('cte_os', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id')->index('cte_os_empresa_id_foreign');
            $table->unsignedInteger('emitente_id')->index('cte_os_emitente_id_foreign');
            $table->unsignedInteger('tomador_id')->index('cte_os_tomador_id_foreign');
            $table->unsignedInteger('municipio_envio')->index('cte_os_municipio_envio_foreign');
            $table->unsignedInteger('municipio_inicio')->index('cte_os_municipio_inicio_foreign');
            $table->unsignedInteger('municipio_fim')->index('cte_os_municipio_fim_foreign');
            $table->unsignedInteger('veiculo_id')->nullable()->index('cte_os_veiculo_id_foreign');
            $table->unsignedInteger('usuario_id')->index('cte_os_usuario_id_foreign');
            $table->string('modal', 2);
            $table->string('cst', 3)->default('00');
            $table->decimal('perc_icms', 5)->default(0);
            $table->decimal('valor_transporte', 10);
            $table->decimal('valor_receber', 10);
            $table->string('descricao_servico', 100)->default('');
            $table->decimal('quantidade_carga', 12, 4);
            $table->unsignedInteger('natureza_id')->index('cte_os_natureza_id_foreign');
            $table->integer('tomador');
            $table->integer('sequencia_cce');
            $table->string('observacao', 200);
            $table->integer('numero_emissao')->default(0);
            $table->string('chave', 48);
            $table->enum('estado', ['NOVO', 'APROVADO', 'CANCELADO', 'REJEITADO']);
            $table->timestamp('data_emissao')->nullable();
            $table->string('data_viagem', 10);
            $table->string('horario_viagem', 5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cte_os');
    }
};
