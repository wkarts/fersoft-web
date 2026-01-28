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
        Schema::create('cliente_oticas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cliente_id')->nullable()->index('cliente_oticas_cliente_id_foreign');
            $table->decimal('esf_od_longe', 6);
            $table->decimal('cil_od_longe', 6);
            $table->decimal('eixo_od_longe', 6);
            $table->decimal('dnp_od_longe', 6);
            $table->decimal('dp_od_longe', 6);
            $table->decimal('esf_oe_longe', 6);
            $table->decimal('cli_oe_longe', 6);
            $table->decimal('eixo_oe_longe', 6);
            $table->decimal('dnp_oe_longe', 6);
            $table->decimal('esf_od_perto', 6);
            $table->decimal('cil_od_perto', 6);
            $table->decimal('eixo_od_perto', 6);
            $table->decimal('adicao_od_perto', 6);
            $table->decimal('altura_od_perto', 6);
            $table->decimal('dnp_od_perto', 6);
            $table->decimal('dp_od_perto', 6);
            $table->decimal('esf_oe_perto', 6);
            $table->decimal('cil_oe_perto', 6);
            $table->decimal('eixo_oe_perto', 6);
            $table->decimal('adicao_oe_perto', 6);
            $table->decimal('altura_oe_perto', 6);
            $table->decimal('dnp_oe_perto', 6);
            $table->string('armacao', 50);
            $table->integer('qtd_armacao');
            $table->decimal('valor_armacao', 10);
            $table->string('lente', 50);
            $table->integer('qtd_lente');
            $table->decimal('valor_lente', 10);
            $table->string('tratamento', 100);
            $table->string('medico', 100);
            $table->string('tipo_lente', 50);
            $table->integer('previsao_retorno_dias');
            $table->string('data', 10);
            $table->string('referencia', 100);
            $table->string('observacao', 200);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_oticas');
    }
};
