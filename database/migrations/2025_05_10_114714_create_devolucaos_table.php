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
        Schema::create('devolucaos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('devolucaos_empresa_id_foreign');
            $table->unsignedInteger('fornecedor_id')->index('devolucaos_fornecedor_id_foreign');
            $table->unsignedInteger('usuario_id')->index('devolucaos_usuario_id_foreign');
            $table->unsignedInteger('natureza_id')->index('devolucaos_natureza_id_foreign');
            $table->unsignedInteger('transportadora_id')->nullable()->index('devolucaos_transportadora_id_foreign');
            $table->timestamp('data_registro')->useCurrent();
            $table->decimal('valor_integral', 10);
            $table->decimal('valor_devolvido', 10);
            $table->string('motivo', 100);
            $table->string('observacao', 150);
            $table->integer('estado');
            $table->boolean('devolucao_parcial');
            $table->string('chave_nf_entrada', 48);
            $table->integer('nNf');
            $table->decimal('vFrete', 10);
            $table->decimal('vDesc', 10);
            $table->string('chave_gerada', 44);
            $table->integer('numero_gerado');
            $table->integer('tipo');
            $table->integer('sequencia_cce')->default(0);
            $table->string('transportadora_nome', 100)->default('');
            $table->string('transportadora_cidade', 50)->default('');
            $table->string('transportadora_uf', 2)->default('');
            $table->string('transportadora_cpf_cnpj', 18)->default('');
            $table->string('transportadora_ie', 15)->default('');
            $table->string('transportadora_endereco', 120)->default('');
            $table->decimal('frete_quantidade', 6)->default(0);
            $table->string('frete_especie', 20)->default('');
            $table->string('frete_marca', 20)->default('');
            $table->string('frete_numero', 20)->default('');
            $table->integer('frete_tipo')->default(0);
            $table->string('veiculo_placa', 10)->default('');
            $table->string('veiculo_uf', 2)->default('');
            $table->decimal('frete_peso_bruto', 10, 3)->default(0);
            $table->decimal('frete_peso_liquido', 10, 3)->default(0);
            $table->decimal('despesa_acessorias', 10)->default(0);
            $table->boolean('altera_manual')->default(false);
            $table->decimal('vbc_manual', 10)->default(0);
            $table->unsignedInteger('filial_id')->nullable()->index('devolucaos_filial_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devolucaos');
    }
};
