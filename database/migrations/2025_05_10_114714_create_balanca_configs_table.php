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
        Schema::create('balanca_configs', function (Blueprint $table) {
            $table->comment('Configurações de balanças instaladas na empresa');
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id')->nullable()->index('balanca_configs_empresa_id_foreign')->comment('ID da empresa relacionada');
            $table->unsignedInteger('usuario_id')->nullable()->index('balanca_configs_usuario_id_foreign')->comment('ID do usuário responsável');
            $table->string('descricao', 100)->comment('Identificação da Balança');
            $table->string('modelo', 100)->index()->comment('Modelo da balança');
            $table->string('marca', 100)->index()->comment('Marca da balança');
            $table->string('port', 50)->nullable()->comment('Porta de comunicação (COM1 a COM20 ou personalizado)');
            $table->integer('velocidade')->nullable()->comment('Velocidade (baud rate)');
            $table->integer('bits')->nullable()->comment('Bits de dados (7 ou 8)');
            $table->string('paridade', 10)->nullable()->comment('Paridade (PAR, IMPAR, NENHUM)');
            $table->string('bits_stop', 10)->nullable()->comment('Stop bits (1, 1.5, 2)');
            $table->string('cabecalho', 50)->nullable()->comment('Cabeçalho do protocolo de comunicação');
            $table->string('rodape', 50)->nullable()->comment('Rodapé do protocolo de comunicação');
            $table->integer('timeout')->nullable()->comment('Tempo limite para leitura (em segundos)');
            $table->string('serie_number', 100)->nullable()->comment('Número de série da balança');
            $table->string('backend_server_address', 100)->nullable()->comment('Número de série da balança');
            $table->boolean('ativo')->default(true)->comment('Status ativo ou inativo');
            $table->timestamp('data_install')->useCurrent()->comment('Data de instalação');
            $table->text('observacoes')->nullable()->comment('Observações adicionais');
            $table->enum('tipo', ['analitica', 'animais', 'animais_suspensa', 'antropometrica', 'bancada', 'cadeira_rodas', 'checkout', 'contadora', 'digital_inteligente', 'dosadora', 'etiquetadora', 'ferroviaria', 'gancho', 'graos', 'grua', 'hospitalar', 'imc', 'joias', 'mercado', 'microbalanca', 'palete', 'pediatrica', 'plataforma', 'piso', 'portatil', 'postal', 'precisao', 'preco_calculado', 'rodoviaria', 'tanque'])->default('mercado')->index()->comment('Tipo da balança');
            $table->string('token', 100)->unique()->comment('Token de autenticação único');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balanca_configs');
    }
};
