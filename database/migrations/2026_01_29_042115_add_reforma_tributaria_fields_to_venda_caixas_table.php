<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venda_caixas', function (Blueprint $table) {
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_QBCMONO'))       $table->decimal('TOTAL_QBCMONO', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_ICMSMONO'))      $table->decimal('TOTAL_ICMSMONO', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_QBCMONORETEN'))  $table->decimal('TOTAL_QBCMONORETEN', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_ICMSMONORETEN')) $table->decimal('TOTAL_ICMSMONORETEN', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_QBCMONORET'))    $table->decimal('TOTAL_QBCMONORET', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_ICMSMONORET'))   $table->decimal('TOTAL_ICMSMONORET', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'DESTINO_OPERACAO'))    $table->integer('DESTINO_OPERACAO')->nullable();

            if (!Schema::hasColumn('venda_caixas', 'RET_BC_IRRF'))         $table->decimal('RET_BC_IRRF', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'RET_ALIQ_IRRF'))       $table->decimal('RET_ALIQ_IRRF', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'RET_VIRRF'))           $table->decimal('RET_VIRRF', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'RET_BC_PIS'))          $table->decimal('RET_BC_PIS', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'RET_ALIQ_PIS'))        $table->decimal('RET_ALIQ_PIS', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'RET_VPIS'))            $table->decimal('RET_VPIS', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'RET_BC_COFINS'))       $table->decimal('RET_BC_COFINS', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'RET_ALIQ_COFINS'))     $table->decimal('RET_ALIQ_COFINS', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'RET_VCOFINS'))         $table->decimal('RET_VCOFINS', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'RET_BC_CSLL'))         $table->decimal('RET_BC_CSLL', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'RET_ALIQ_CSLL'))       $table->decimal('RET_ALIQ_CSLL', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'RET_VCSLL'))           $table->decimal('RET_VCSLL', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'FLAG_NORMATIVA_IRRF')) $table->string('FLAG_NORMATIVA_IRRF', 1)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'TOTAL_IPI_DEVOLVIDO')) $table->decimal('TOTAL_IPI_DEVOLVIDO', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'FK_PES_RETIRADA'))     $table->integer('FK_PES_RETIRADA')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'FLAG_END_ENTREGA'))    $table->string('FLAG_END_ENTREGA', 1)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_II'))            $table->decimal('TOTAL_II', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'NRECEITUARIO'))        $table->string('NRECEITUARIO', 30)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'CPFRESPTEC'))          $table->string('CPFRESPTEC', 14)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'TIPO_GUIA_TRANSITO'))  $table->integer('TIPO_GUIA_TRANSITO')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'UF_GUIA_TRANSITO'))    $table->string('UF_GUIA_TRANSITO', 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'SERIE_GUIA_TRANSITO')) $table->string('SERIE_GUIA_TRANSITO', 10)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'NUM_GUIA_TRANSITO'))   $table->string('NUM_GUIA_TRANSITO', 10)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'TOTAL_IS'))                          $table->decimal('TOTAL_IS', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_BC_IBS_CBS'))                  $table->decimal('TOTAL_BC_IBS_CBS', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_IBS'))                         $table->decimal('TOTAL_IBS', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_IBS_CRED_PRES'))               $table->decimal('TOTAL_IBS_CRED_PRES', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_IBS_CRED_PRES_COND_SUS'))      $table->decimal('TOTAL_IBS_CRED_PRES_COND_SUS', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'TOTAL_IBS_UF_DIF'))                  $table->decimal('TOTAL_IBS_UF_DIF', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_IBS_UF_DEV_TRIB'))             $table->decimal('TOTAL_IBS_UF_DEV_TRIB', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_IBS_UF'))                      $table->decimal('TOTAL_IBS_UF', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'TOTAL_IBS_MUN_DIF'))                 $table->decimal('TOTAL_IBS_MUN_DIF', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_IBS_MUN_DEV_TRIB'))            $table->decimal('TOTAL_IBS_MUN_DEV_TRIB', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_IBS_MUN'))                     $table->decimal('TOTAL_IBS_MUN', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'TOTAL_CBS_DIF'))                     $table->decimal('TOTAL_CBS_DIF', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_CBS_DEV_TRIB'))                $table->decimal('TOTAL_CBS_DEV_TRIB', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_CBS'))                         $table->decimal('TOTAL_CBS', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_CBS_CRED_PRES'))               $table->decimal('TOTAL_CBS_CRED_PRES', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_CBS_CRED_PRES_COND_SUS'))      $table->decimal('TOTAL_CBS_CRED_PRES_COND_SUS', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'TOTAL_IBS_MONO'))                    $table->decimal('TOTAL_IBS_MONO', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_CBS_MONO'))                    $table->decimal('TOTAL_CBS_MONO', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_IBS_MONO_RETEN'))              $table->decimal('TOTAL_IBS_MONO_RETEN', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_CBS_MONO_RETEN'))              $table->decimal('TOTAL_CBS_MONO_RETEN', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_IBS_MONO_RET'))                $table->decimal('TOTAL_IBS_MONO_RET', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_CBS_MONO_RET'))                $table->decimal('TOTAL_CBS_MONO_RET', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'TOTAL_NF_IBC_CBS_IS'))               $table->decimal('TOTAL_NF_IBC_CBS_IS', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TOTAL_IBS_CBS'))                     $table->decimal('TOTAL_IBS_CBS', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'TIPO_NFCREDITO'))                    $table->integer('TIPO_NFCREDITO')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TIPO_NFDEBITO'))                     $table->integer('TIPO_NFDEBITO')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TIPO_ENTEGOV'))                      $table->integer('TIPO_ENTEGOV')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'PERC_REDUTOR_GOV'))                  $table->decimal('PERC_REDUTOR_GOV', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'TIPO_OPERGOV'))                      $table->integer('TIPO_OPERGOV')->nullable();

            if (!Schema::hasColumn('venda_caixas', 'ELOQUENT_UUID'))     $table->uuid('ELOQUENT_UUID')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'CREATED_AT'))        $table->timestamp('CREATED_AT')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'UPDATED_AT'))        $table->timestamp('UPDATED_AT')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'DELETED_AT'))        $table->timestamp('DELETED_AT')->nullable();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('DROP TRIGGER IF EXISTS venda_caixas_BI0');
            DB::unprepared("
                CREATE TRIGGER venda_caixas_BI0
                BEFORE INSERT ON venda_caixas
                FOR EACH ROW
                BEGIN
                    IF (NEW.ELOQUENT_UUID IS NULL) THEN SET NEW.ELOQUENT_UUID = UUID(); END IF;
                    IF (NEW.CREATED_AT   IS NULL) THEN SET NEW.CREATED_AT   = CURRENT_TIMESTAMP; END IF;
                END
            ");

            DB::statement('DROP TRIGGER IF EXISTS venda_caixas_BU0');
            DB::unprepared("
                CREATE TRIGGER venda_caixas_BU0
                BEFORE UPDATE ON venda_caixas
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
            DB::statement('DROP TRIGGER IF EXISTS venda_caixas_BI0');
            DB::statement('DROP TRIGGER IF EXISTS venda_caixas_BU0');
        }

        Schema::table('venda_caixas', function (Blueprint $table) {
            $cols = [
                'TOTAL_QBCMONO','TOTAL_ICMSMONO','TOTAL_QBCMONORETEN','TOTAL_ICMSMONORETEN','TOTAL_QBCMONORET','TOTAL_ICMSMONORET',
                'DESTINO_OPERACAO',
                'RET_BC_IRRF','RET_ALIQ_IRRF','RET_VIRRF',
                'RET_BC_PIS','RET_ALIQ_PIS','RET_VPIS',
                'RET_BC_COFINS','RET_ALIQ_COFINS','RET_VCOFINS',
                'RET_BC_CSLL','RET_ALIQ_CSLL','RET_VCSLL',
                'FLAG_NORMATIVA_IRRF',
                'TOTAL_IPI_DEVOLVIDO','FK_PES_RETIRADA','FLAG_END_ENTREGA','TOTAL_II',
                'NRECEITUARIO','CPFRESPTEC',
                'TIPO_GUIA_TRANSITO','UF_GUIA_TRANSITO','SERIE_GUIA_TRANSITO','NUM_GUIA_TRANSITO',
                'TOTAL_IS','TOTAL_BC_IBS_CBS','TOTAL_IBS','TOTAL_IBS_CRED_PRES','TOTAL_IBS_CRED_PRES_COND_SUS',
                'TOTAL_IBS_UF_DIF','TOTAL_IBS_UF_DEV_TRIB','TOTAL_IBS_UF',
                'TOTAL_IBS_MUN_DIF','TOTAL_IBS_MUN_DEV_TRIB','TOTAL_IBS_MUN',
                'TOTAL_CBS_DIF','TOTAL_CBS_DEV_TRIB','TOTAL_CBS','TOTAL_CBS_CRED_PRES','TOTAL_CBS_CRED_PRES_COND_SUS',
                'TOTAL_IBS_MONO','TOTAL_CBS_MONO','TOTAL_IBS_MONO_RETEN','TOTAL_CBS_MONO_RETEN','TOTAL_IBS_MONO_RET','TOTAL_CBS_MONO_RET',
                'TOTAL_NF_IBC_CBS_IS','TOTAL_IBS_CBS',
                'TIPO_NFCREDITO','TIPO_NFDEBITO','TIPO_ENTEGOV','PERC_REDUTOR_GOV','TIPO_OPERGOV',
                'ELOQUENT_UUID','CREATED_AT','UPDATED_AT','DELETED_AT'
            ];

            foreach ($cols as $c) {
                if (Schema::hasColumn('venda_caixas', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
