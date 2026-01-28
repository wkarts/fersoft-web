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
        Schema::create('conta_pagars', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('conta_pagars_empresa_id_foreign');
            $table->unsignedInteger('compra_id')->nullable()->index('conta_pagars_compra_id_foreign');
            $table->unsignedInteger('categoria_id')->index('conta_pagars_categoria_id_foreign');
            $table->integer('fornecedor_id')->default(0);
            $table->string('referencia');
            $table->decimal('valor_integral', 16, 7);
            $table->decimal('valor_pago', 16, 7)->default(0);
            $table->timestamp('date_register')->useCurrent();
            $table->date('data_vencimento');
            $table->timestamp('data_pagamento')->nullable();
            $table->boolean('status')->default(false);
            $table->string('tipo_pagamento', 20);
            $table->integer('numero_nota_fiscal')->default(0);
            $table->timestamps();
            $table->unsignedInteger('filial_id')->nullable()->index('conta_pagars_filial_id_foreign');
            $table->string('observacao', 100)->default('');
            $table->boolean('estorno')->default(false);
            $table->string('motivo_estorno', 100)->nullable();
            $table->decimal('valor_inss', 10)->nullable();
            $table->decimal('valor_iss', 10)->nullable();
            $table->decimal('valor_pis', 10)->nullable();
            $table->decimal('valor_cofins', 10)->nullable();
            $table->decimal('valor_ir', 10)->nullable();
            $table->decimal('outras_retencoes', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conta_pagars');
    }
};
