<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('compras')) {
            return;
        }

        Schema::table('compras', function (Blueprint $table) {
            // Totais por compra (espelhando a venda) - (15,2)
            if (!Schema::hasColumn('compras', 'total_bc_ibs_cbs'))         $table->decimal('total_bc_ibs_cbs', 15, 2)->nullable();

            if (!Schema::hasColumn('compras', 'total_ibs_uf_dif'))         $table->decimal('total_ibs_uf_dif', 15, 2)->nullable();
            if (!Schema::hasColumn('compras', 'total_ibs_uf_dev_trib'))    $table->decimal('total_ibs_uf_dev_trib', 15, 2)->nullable();
            if (!Schema::hasColumn('compras', 'total_ibs_uf'))             $table->decimal('total_ibs_uf', 15, 2)->nullable();

            if (!Schema::hasColumn('compras', 'total_ibs_mun_dif'))        $table->decimal('total_ibs_mun_dif', 15, 2)->nullable();
            if (!Schema::hasColumn('compras', 'total_ibs_mun_dev_trib'))   $table->decimal('total_ibs_mun_dev_trib', 15, 2)->nullable();
            if (!Schema::hasColumn('compras', 'total_ibs_mun'))            $table->decimal('total_ibs_mun', 15, 2)->nullable();

            if (!Schema::hasColumn('compras', 'total_cbs_dif'))            $table->decimal('total_cbs_dif', 15, 2)->nullable();
            if (!Schema::hasColumn('compras', 'total_cbs_dev_trib'))       $table->decimal('total_cbs_dev_trib', 15, 2)->nullable();
            if (!Schema::hasColumn('compras', 'total_cbs'))                $table->decimal('total_cbs', 15, 2)->nullable();

            if (!Schema::hasColumn('compras', 'total_is'))                 $table->decimal('total_is', 15, 2)->nullable();

            // Monofásico / retenções (15,2)
            if (!Schema::hasColumn('compras', 'total_ibs_mono'))           $table->decimal('total_ibs_mono', 15, 2)->nullable();
            if (!Schema::hasColumn('compras', 'total_cbs_mono'))           $table->decimal('total_cbs_mono', 15, 2)->nullable();

            if (!Schema::hasColumn('compras', 'total_ibs_mono_reten'))     $table->decimal('total_ibs_mono_reten', 15, 2)->nullable();
            if (!Schema::hasColumn('compras', 'total_cbs_mono_reten'))     $table->decimal('total_cbs_mono_reten', 15, 2)->nullable();

            if (!Schema::hasColumn('compras', 'total_ibs_mono_ret'))       $table->decimal('total_ibs_mono_ret', 15, 2)->nullable();
            if (!Schema::hasColumn('compras', 'total_cbs_mono_ret'))       $table->decimal('total_cbs_mono_ret', 15, 2)->nullable();

            // Totais consolidados (15,2)
            if (!Schema::hasColumn('compras', 'total_ibs'))                $table->decimal('total_ibs', 15, 2)->nullable();
            if (!Schema::hasColumn('compras', 'total_ibs_cbs'))            $table->decimal('total_ibs_cbs', 15, 2)->nullable();
        });
    }

    public function down(): void
    {
        // conservador: não remove colunas
    }
};
