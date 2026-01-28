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
        Schema::create('item_remessa_nves', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('remessa_id')->index('item_remessa_nves_remessa_id_foreign');
            $table->unsignedInteger('produto_id')->index('item_remessa_nves_produto_id_foreign');
            $table->decimal('quantidade', 16, 7);
            $table->decimal('valor_unitario', 16, 7);
            $table->decimal('sub_total', 16, 7);
            $table->string('cfop', 4);
            $table->string('cst_csosn', 3);
            $table->string('cst_pis', 3);
            $table->string('cst_cofins', 3);
            $table->string('cst_ipi', 3);
            $table->decimal('perc_icms');
            $table->decimal('perc_pis');
            $table->decimal('perc_cofins');
            $table->decimal('perc_ipi');
            $table->decimal('pRedBC', 10, 4);
            $table->decimal('vbc_icms', 10, 4);
            $table->decimal('vbc_pis', 10, 4);
            $table->decimal('vbc_cofins', 10, 4);
            $table->decimal('vbc_ipi', 10, 4);
            $table->decimal('valor_icms', 10, 4);
            $table->decimal('valor_pis', 10, 4);
            $table->decimal('valor_cofins', 10, 4);
            $table->decimal('valor_ipi', 10, 4);
            $table->decimal('vBCSTRet')->default(0);
            $table->decimal('vFrete')->default(0);
            $table->decimal('modBCST');
            $table->decimal('vBCST');
            $table->decimal('pICMSST');
            $table->decimal('vICMSST');
            $table->decimal('pMVAST');
            $table->string('x_pedido', 30);
            $table->string('num_item_pedido', 30);
            $table->string('cest', 10);
            $table->string('produto_nome', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_remessa_nves');
    }
};
