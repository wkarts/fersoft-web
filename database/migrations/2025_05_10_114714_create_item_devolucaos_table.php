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
        Schema::create('item_devolucaos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cod', 10);
            $table->string('nome', 150);
            $table->string('ncm', 10);
            $table->string('cfop', 10);
            $table->string('codBarras', 13);
            $table->decimal('valor_unit', 14, 4);
            $table->decimal('quantidade', 10, 4);
            $table->boolean('item_parcial');
            $table->string('unidade_medida', 8);
            $table->string('cst_csosn', 3);
            $table->string('cst_pis', 3);
            $table->string('cst_cofins', 3);
            $table->string('cst_ipi', 3);
            $table->decimal('perc_icms');
            $table->decimal('perc_pis');
            $table->decimal('perc_cofins');
            $table->decimal('perc_ipi');
            $table->decimal('pRedBC', 10, 4);
            $table->decimal('vBCSTRet')->default(0);
            $table->decimal('vFrete')->default(0);
            $table->unsignedInteger('devolucao_id')->index('item_devolucaos_devolucao_id_foreign');
            $table->decimal('modBCST');
            $table->decimal('vBCST');
            $table->decimal('pICMSST');
            $table->decimal('vICMSST');
            $table->decimal('pMVAST');
            $table->integer('orig');
            $table->decimal('pST', 10);
            $table->decimal('vICMSSubstituto', 10);
            $table->decimal('vICMSSTRet', 10);
            $table->string('codigo_anp', 10)->default('');
            $table->string('descricao_anp', 95)->default('');
            $table->decimal('perc_glp', 5)->default(0);
            $table->decimal('perc_gnn', 5)->default(0);
            $table->decimal('perc_gni', 5)->default(0);
            $table->string('uf_cons', 2)->default('');
            $table->decimal('valor_partida', 10)->default(0);
            $table->string('unidade_tributavel', 4)->default('');
            $table->decimal('quantidade_tributavel', 10)->default(0);
            $table->decimal('qBCMonoRet', 10, 4)->default(0);
            $table->decimal('adRemICMSRet', 10, 3)->default(0);
            $table->decimal('vICMSMonoRet', 10, 3)->default(0);
            $table->decimal('vbc_manual', 10, 4);
            $table->decimal('vicms_manual', 10, 4);
            $table->decimal('vpis_manual', 10, 4);
            $table->decimal('vcofins_manual', 10, 4);
            $table->decimal('vipi_manual', 10, 4);
            $table->string('cest', 10)->nullable();
            $table->string('cBenef', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_devolucaos');
    }
};
