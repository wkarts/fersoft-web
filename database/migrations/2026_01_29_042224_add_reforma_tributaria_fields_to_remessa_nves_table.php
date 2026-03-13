<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remessa_nves', function (Blueprint $table) {
            if (!Schema::hasColumn('remessa_nves', 'total_qbcmono'))       $table->decimal('total_qbcmono', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_icmsmono'))      $table->decimal('total_icmsmono', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_qbcmonoreten'))  $table->decimal('total_qbcmonoreten', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_icmsmonoreten')) $table->decimal('total_icmsmonoreten', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_qbcmonoret'))    $table->decimal('total_qbcmonoret', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_icmsmonoret'))   $table->decimal('total_icmsmonoret', 15, 2)->nullable();

            if (!Schema::hasColumn('remessa_nves', 'destino_operacao'))    $table->integer('destino_operacao')->nullable();

            if (!Schema::hasColumn('remessa_nves', 'ret_bc_irrf'))         $table->decimal('ret_bc_irrf', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'ret_aliq_irrf'))       $table->decimal('ret_aliq_irrf', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'ret_virrf'))           $table->decimal('ret_virrf', 15, 2)->nullable();

            if (!Schema::hasColumn('remessa_nves', 'ret_bc_pis'))          $table->decimal('ret_bc_pis', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'ret_aliq_pis'))        $table->decimal('ret_aliq_pis', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'ret_vpis'))            $table->decimal('ret_vpis', 15, 2)->nullable();

            if (!Schema::hasColumn('remessa_nves', 'ret_bc_cofins'))       $table->decimal('ret_bc_cofins', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'ret_aliq_cofins'))     $table->decimal('ret_aliq_cofins', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'ret_vcofins'))         $table->decimal('ret_vcofins', 15, 2)->nullable();

            if (!Schema::hasColumn('remessa_nves', 'ret_bc_csll'))         $table->decimal('ret_bc_csll', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'ret_aliq_csll'))       $table->decimal('ret_aliq_csll', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'ret_vcsll'))           $table->decimal('ret_vcsll', 15, 2)->nullable();

            if (!Schema::hasColumn('remessa_nves', 'flag_normativa_irrf')) $table->string('flag_normativa_irrf', 1)->nullable();

            if (!Schema::hasColumn('remessa_nves', 'total_ipi_devolvido')) $table->decimal('total_ipi_devolvido', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'fk_pes_retirada'))     $table->integer('fk_pes_retirada')->nullable();
            if (!Schema::hasColumn('remessa_nves', 'flag_end_entrega'))    $table->string('flag_end_entrega', 1)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_ii'))            $table->decimal('total_ii', 15, 2)->nullable();

            if (!Schema::hasColumn('remessa_nves', 'nreceituario'))        $table->string('nreceituario', 30)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'cpfresptec'))          $table->string('cpfresptec', 14)->nullable();

            if (!Schema::hasColumn('remessa_nves', 'tipo_guia_transito'))  $table->integer('tipo_guia_transito')->nullable();
            if (!Schema::hasColumn('remessa_nves', 'uf_guia_transito'))    $table->string('uf_guia_transito', 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'serie_guia_transito')) $table->string('serie_guia_transito', 10)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'num_guia_transito'))   $table->string('num_guia_transito', 10)->nullable();

            if (!Schema::hasColumn('remessa_nves', 'total_is'))                          $table->decimal('total_is', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_bc_ibs_cbs'))                  $table->decimal('total_bc_ibs_cbs', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_ibs'))                         $table->decimal('total_ibs', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_ibs_cred_pres'))               $table->decimal('total_ibs_cred_pres', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_ibs_cred_pres_cond_sus'))      $table->decimal('total_ibs_cred_pres_cond_sus', 15, 2)->nullable();

            if (!Schema::hasColumn('remessa_nves', 'total_ibs_uf_dif'))                  $table->decimal('total_ibs_uf_dif', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_ibs_uf_dev_trib'))             $table->decimal('total_ibs_uf_dev_trib', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_ibs_uf'))                      $table->decimal('total_ibs_uf', 15, 2)->nullable();

            if (!Schema::hasColumn('remessa_nves', 'total_ibs_mun_dif'))                 $table->decimal('total_ibs_mun_dif', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_ibs_mun_dev_trib'))            $table->decimal('total_ibs_mun_dev_trib', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_ibs_mun'))                     $table->decimal('total_ibs_mun', 15, 2)->nullable();

            if (!Schema::hasColumn('remessa_nves', 'total_cbs_dif'))                     $table->decimal('total_cbs_dif', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_cbs_dev_trib'))                $table->decimal('total_cbs_dev_trib', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_cbs'))                         $table->decimal('total_cbs', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_cbs_cred_pres'))               $table->decimal('total_cbs_cred_pres', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_cbs_cred_pres_cond_sus'))      $table->decimal('total_cbs_cred_pres_cond_sus', 15, 2)->nullable();

            if (!Schema::hasColumn('remessa_nves', 'total_ibs_mono'))                    $table->decimal('total_ibs_mono', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_cbs_mono'))                    $table->decimal('total_cbs_mono', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_ibs_mono_reten'))              $table->decimal('total_ibs_mono_reten', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_cbs_mono_reten'))              $table->decimal('total_cbs_mono_reten', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_ibs_mono_ret'))                $table->decimal('total_ibs_mono_ret', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_cbs_mono_ret'))                $table->decimal('total_cbs_mono_ret', 15, 2)->nullable();

            if (!Schema::hasColumn('remessa_nves', 'total_nf_ibc_cbs_is'))               $table->decimal('total_nf_ibc_cbs_is', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'total_ibs_cbs'))                     $table->decimal('total_ibs_cbs', 15, 2)->nullable();

            if (!Schema::hasColumn('remessa_nves', 'tipo_nfcredito'))                    $table->integer('tipo_nfcredito')->nullable();
            if (!Schema::hasColumn('remessa_nves', 'tipo_nfdebito'))                     $table->integer('tipo_nfdebito')->nullable();
            if (!Schema::hasColumn('remessa_nves', 'tipo_entegov'))                      $table->integer('tipo_entegov')->nullable();
            if (!Schema::hasColumn('remessa_nves', 'perc_redutor_gov'))                  $table->decimal('perc_redutor_gov', 15, 2)->nullable();
            if (!Schema::hasColumn('remessa_nves', 'tipo_opergov'))                      $table->integer('tipo_opergov')->nullable();

            if (!Schema::hasColumn('remessa_nves', 'eloquent_uuid'))     $table->uuid('eloquent_uuid')->nullable();
            if (!Schema::hasColumn('remessa_nves', 'created_at'))        $table->timestamp('created_at')->nullable();
            if (!Schema::hasColumn('remessa_nves', 'updated_at'))        $table->timestamp('updated_at')->nullable();
            if (!Schema::hasColumn('remessa_nves', 'deleted_at'))        $table->timestamp('deleted_at')->nullable();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('DROP TRIGGER IF EXISTS remessa_nves_BI0');
            DB::unprepared("
                CREATE TRIGGER remessa_nves_BI0
                BEFORE INSERT ON remessa_nves
                FOR EACH ROW
                BEGIN
                    IF (NEW.ELOQUENT_UUID IS NULL) THEN SET NEW.ELOQUENT_UUID = UUID(); END IF;
                    IF (NEW.CREATED_AT   IS NULL) THEN SET NEW.CREATED_AT   = CURRENT_TIMESTAMP; END IF;
                END
            ");

            DB::statement('DROP TRIGGER IF EXISTS remessa_nves_BU0');
            DB::unprepared("
                CREATE TRIGGER remessa_nves_BU0
                BEFORE UPDATE ON remessa_nves
                FOR EACH ROW
                BEGIN
                    IF (NEW.ELOQUENT_UUID IS NULL) THEN SET NEW.ELOQUENT_UUID = UUID(); END IF;
                    SET NEW.UPDATED_AT = CURRENT_TIMESTAMP;
                END
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('DROP TRIGGER IF EXISTS remessa_nves_BI0');
            DB::statement('DROP TRIGGER IF EXISTS remessa_nves_BU0');
        }

        Schema::table('remessa_nves', function (Blueprint $table) {
            $cols = [
                'total_qbcmono','total_icmsmono','total_qbcmonoreten','total_icmsmonoreten','total_qbcmonoret','total_icmsmonoret',
                'destino_operacao',
                'ret_bc_irrf','ret_aliq_irrf','ret_virrf',
                'ret_bc_pis','ret_aliq_pis','ret_vpis',
                'ret_bc_cofins','ret_aliq_cofins','ret_vcofins',
                'ret_bc_csll','ret_aliq_csll','ret_vcsll',
                'flag_normativa_irrf',
                'total_ipi_devolvido','fk_pes_retirada','flag_end_entrega','total_ii',
                'nreceituario','cpfresptec',
                'tipo_guia_transito','uf_guia_transito','serie_guia_transito','num_guia_transito',
                'total_is','total_bc_ibs_cbs','total_ibs','total_ibs_cred_pres','total_ibs_cred_pres_cond_sus',
                'total_ibs_uf_dif','total_ibs_uf_dev_trib','total_ibs_uf',
                'total_ibs_mun_dif','total_ibs_mun_dev_trib','total_ibs_mun',
                'total_cbs_dif','total_cbs_dev_trib','total_cbs','total_cbs_cred_pres','total_cbs_cred_pres_cond_sus',
                'total_ibs_mono','total_cbs_mono','total_ibs_mono_reten','total_cbs_mono_reten','total_ibs_mono_ret','total_cbs_mono_ret',
                'total_nf_ibc_cbs_is','total_ibs_cbs',
                'tipo_nfcredito','tipo_nfdebito','tipo_entegov','perc_redutor_gov','tipo_opergov',
                'eloquent_uuid','created_at','updated_at','deleted_at'
            ];

            foreach ($cols as $c) {
                if (Schema::hasColumn('remessa_nves', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
