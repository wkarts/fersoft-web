<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('fatura_eng_itens')) {
            return;
        }

        Schema::create('fatura_eng_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fatura_eng_id');
            $table->string('tipo_item', 30)->default('Servico');
            $table->unsignedInteger('servico_id')->nullable();
            $table->unsignedInteger('produto_id')->nullable();
            $table->string('descricao', 255)->nullable();
            $table->decimal('quantidade', 15, 4)->default(1);
            $table->decimal('valor_unitario', 15, 2)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->timestamps();

            $table->index('fatura_eng_id', 'fatura_eng_itens_fatura_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('fatura_eng_itens');
    }
};
