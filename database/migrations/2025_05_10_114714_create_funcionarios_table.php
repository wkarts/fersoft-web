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
        Schema::create('funcionarios', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('funcionarios_empresa_id_foreign');
            $table->unsignedInteger('usuario_id')->nullable()->index('funcionarios_usuario_id_foreign');
            $table->decimal('percentual_comissao', 6)->default(0);
            $table->decimal('salario', 10)->default(0);
            $table->string('nome', 100);
            $table->string('cpf', 15)->default('000.000.000-00');
            $table->string('rg', 15);
            $table->string('rua', 80);
            $table->string('numero', 10);
            $table->string('bairro', 50);
            $table->string('telefone', 20);
            $table->string('cnh')->nullable();
            $table->enum('categoria_cnh', ['A', 'B', 'C', 'D', 'E'])->nullable();
            $table->date('vencimento_cnh')->nullable();
            $table->enum('status_motorista', ['Ativo', 'Inativo'])->default('Ativo');
            $table->string('celular', 20)->default('00 00000 0000');
            $table->string('email', 40)->default('');
            $table->date('data_registro');
            $table->date('data_nascimento')->nullable();
            $table->date('data_admissao')->nullable();
            $table->string('tipo_sanguineo', 3)->nullable();
            $table->enum('status_funcionario', ['Ativo', 'Desligado'])->default('Ativo');
            $table->string('numero_registro', 50)->nullable();
            $table->text('observacoes')->nullable();
            $table->string('foto_funcionario', 200)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funcionarios');
    }
};
