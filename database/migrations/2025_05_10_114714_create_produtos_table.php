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
        Schema::create('produtos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('produtos_empresa_id_foreign');
            $table->unsignedInteger('categoria_id')->index('produtos_categoria_id_foreign');
            $table->unsignedInteger('sub_categoria_id')->nullable()->index('produtos_sub_categoria_id_foreign');
            $table->unsignedInteger('marca_id')->nullable()->index('produtos_marca_id_foreign');
            $table->string('nome', 100);
            $table->string('cor', 20);
            $table->integer('referencia_balanca')->default(0);
            $table->decimal('valor_venda', 22, 7)->default(0);
            $table->decimal('valor_compra', 22, 7)->default(0);
            $table->boolean('reajuste_automatico')->default(false);
            $table->decimal('percentual_lucro', 10)->default(0);
            $table->string('NCM', 13)->default('');
            $table->string('codBarras', 15)->default('');
            $table->string('CEST', 10)->default('');
            $table->string('CST_CSOSN', 3)->default('');
            $table->string('CST_PIS', 3)->default('');
            $table->string('CST_COFINS', 3)->default('');
            $table->string('CST_IPI', 3)->default('');
            $table->string('CST_CSOSN_EXP', 3)->default('');
            $table->string('unidade_compra', 10);
            $table->string('conversao_unitaria')->default('1');
            $table->string('unidade_venda', 10);
            $table->boolean('composto')->default(false);
            $table->boolean('valor_livre');
            $table->decimal('perc_icms', 10)->default(0);
            $table->decimal('perc_pis', 10)->default(0);
            $table->decimal('perc_cofins', 10)->default(0);
            $table->decimal('perc_ipi', 10)->default(0);
            $table->decimal('perc_iss', 10)->default(0);
            $table->string('cListServ', 5)->nullable();
            $table->string('CFOP_saida_estadual', 5);
            $table->string('CFOP_saida_inter_estadual', 5);
            $table->string('codigo_anp', 10);
            $table->string('descricao_anp', 95);
            $table->decimal('perc_glp', 5)->default(0);
            $table->decimal('perc_gnn', 5)->default(0);
            $table->decimal('perc_gni', 5)->default(0);
            $table->decimal('valor_partida', 10)->default(0);
            $table->string('unidade_tributavel', 10)->nullable();
            $table->decimal('quantidade_tributavel', 10)->default(0);
            $table->string('imagem', 100);
            $table->integer('alerta_vencimento');
            $table->boolean('gerenciar_estoque');
            $table->integer('estoque_minimo')->default(0);
            $table->string('referencia', 25)->default('');
            $table->decimal('pRedBC', 5)->default(0);
            $table->string('cBenef', 10)->nullable();
            $table->decimal('largura', 6)->default(0);
            $table->decimal('comprimento', 6)->default(0);
            $table->decimal('altura', 6)->default(0);
            $table->decimal('peso_liquido', 8, 3)->default(0);
            $table->decimal('peso_bruto', 8, 3)->default(0);
            $table->decimal('limite_maximo_desconto', 5)->default(0);
            $table->string('referencia_grade', 20)->default('');
            $table->boolean('grade')->default(false);
            $table->string('str_grade', 20)->default('');
            $table->decimal('perc_icms_interestadual', 10)->default(0);
            $table->decimal('perc_icms_interno', 10)->default(0);
            $table->decimal('perc_fcp_interestadual', 10)->default(0);
            $table->boolean('inativo')->default(false);
            $table->string('renavam', 20)->default('');
            $table->string('placa', 9)->default('');
            $table->string('chassi', 30)->default('');
            $table->string('combustivel', 15)->default('');
            $table->string('ano_modelo', 9)->default('');
            $table->string('cor_veiculo', 20)->default('');
            $table->decimal('valor_locacao', 10, 4)->default(0);
            $table->string('lote', 10)->default('');
            $table->string('vencimento', 10)->default('');
            $table->integer('origem')->default(0);
            $table->string('tipo_dimensao', 15)->default('');
            $table->decimal('perc_comissao', 5)->default(0);
            $table->decimal('valor_comissao', 9)->default(0);
            $table->decimal('acrescimo_perca', 5)->default(0);
            $table->string('nuvemshop_id', 20)->default('');
            $table->string('ifood_id', 40)->default('');
            $table->text('info_tecnica_composto')->nullable();
            $table->text('observacao')->nullable();
            $table->string('CST_CSOSN_entrada', 3)->default('');
            $table->string('CST_PIS_entrada', 3)->default('');
            $table->string('CST_COFINS_entrada', 3)->default('');
            $table->string('CST_IPI_entrada', 3)->default('');
            $table->string('CFOP_entrada_estadual', 5);
            $table->string('CFOP_entrada_inter_estadual', 5);
            $table->decimal('custo_assessor', 10)->default(0);
            $table->boolean('envia_controle_pedidos')->default(false);
            $table->string('cenq_ipi', 3)->default('999');
            $table->integer('tela_pedido_id')->default(0);
            $table->integer('modBCST')->default(0);
            $table->integer('modBC')->default(0);
            $table->decimal('pICMSST', 5)->default(0);
            $table->text('locais');
            $table->decimal('perc_frete', 6)->default(0);
            $table->decimal('perc_outros', 6)->default(0);
            $table->decimal('perc_mlv', 6)->default(0);
            $table->decimal('perc_mva', 6)->default(0);
            $table->decimal('qBCMonoRet', 10, 4)->default(0);
            $table->decimal('adRemICMSRet', 10, 4)->default(0);
            $table->decimal('pBio', 10, 4)->default(0);
            $table->boolean('tipo_servico')->default(false);
            $table->integer('indImport')->default(0);
            $table->string('cUFOrig', 2)->nullable();
            $table->decimal('pOrig', 5)->default(0);
            $table->decimal('peso', 12, 3)->default(0);
            $table->text('info_adicional_item');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
