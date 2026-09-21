<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('contrato_eng_itens')) {
            return;
        }

        Schema::create('contrato_eng_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_eng_id');
            $table->string('tipo_item', 30)->default('Servico');
            $table->unsignedInteger('servico_id')->nullable();
            $table->unsignedInteger('produto_id')->nullable();
            $table->decimal('quantidade_prevista', 15, 4)->default(0);
            $table->decimal('valor_unitario', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->timestamps();

            $table->index('contrato_eng_id', 'contrato_eng_itens_contrato_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contrato_eng_itens');
    }
};
