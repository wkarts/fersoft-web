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
        Schema::create('tributacaos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('tributacaos_empresa_id_foreign');
            $table->decimal('icms', 4);
            $table->decimal('pis', 4);
            $table->decimal('cofins', 4);
            $table->decimal('ipi', 4);
            $table->decimal('perc_ap_cred', 5);
            $table->string('ncm_padrao', 10)->default('0000.00.00');
            $table->string('link_nfse', 200)->default('');
            $table->boolean('exclusao_icms_pis_cofins')->default(false);
            $table->string('regime');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tributacaos');
    }
};
