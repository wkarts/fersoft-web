<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pagamento_cobrancas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conexao_id')->constrained('pagamento_conexoes');
            $table->uuid('referencia')->unique();
            $table->string('origem');
            $table->boolean('vigente')->nullable();
            $table->unsignedBigInteger('conta_receber_id')->nullable();
            $table->unsignedBigInteger('venda_caixa_id')->nullable();
            $table->char('carrinho_hash', 64)->nullable();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('centavos');
            $table->string('metodo', 20);
            $table->string('externo')->nullable();
            $table->string('situacao')->default('emissao');
            $table->string('conciliacao')->nullable();
            $table->string('acao_pendente')->nullable();
            $table->text('url_estorno')->nullable();
            $table->timestamp('baixada_em')->nullable();
            $table->timestamps();
            $table->unique(['conexao_id', 'origem', 'vigente']);
            $table->unique(['conexao_id', 'externo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamento_cobrancas');
    }
};
