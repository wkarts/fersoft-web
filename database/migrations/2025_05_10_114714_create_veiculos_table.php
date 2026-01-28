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
        Schema::create('veiculos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('traccar_id')->nullable()->comment('ID do dispositivo no Traccar');
            $table->unsignedInteger('empresa_id')->index('veiculos_empresa_id_foreign');
            $table->string('placa', 8);
            $table->decimal('quilometragem', 10)->default(0);
            $table->unsignedInteger('motorista_id')->nullable()->index('veiculos_motorista_id_foreign');
            $table->decimal('consumo_medio', 10)->nullable();
            $table->decimal('custo_por_km', 10)->nullable();
            $table->enum('ativo', ['Sim', 'Não'])->default('Sim');
            $table->string('uf', 2);
            $table->string('cor', 10);
            $table->string('marca', 20);
            $table->string('modelo', 20);
            $table->string('rntrc', 12);
            $table->string('taf', 15);
            $table->string('renavam', 12);
            $table->string('numero_registro_estadual', 30);
            $table->string('tipo', 2);
            $table->string('tipo_carroceira', 2);
            $table->string('tipo_rodado', 2);
            $table->string('tara', 10);
            $table->string('capacidade', 10);
            $table->string('proprietario_documento', 20);
            $table->string('proprietario_nome', 40);
            $table->string('proprietario_ie', 13);
            $table->string('proprietario_uf', 2);
            $table->integer('proprietario_tp');
            $table->string('ano_fabricacao', 10)->nullable();
            $table->string('ano_modelo', 10)->nullable();
            $table->unsignedBigInteger('marca_fk')->nullable()->index('veiculos_marca_fk_foreign');
            $table->unsignedBigInteger('modelo_fk')->nullable()->index('veiculos_modelo_fk_foreign');
            $table->string('chassi', 200)->nullable();
            $table->string('combustivel', 30)->nullable();
            $table->unsignedBigInteger('combustivel_fk')->nullable()->index('veiculos_combustivel_fk_foreign');
            $table->string('km_hora', 30)->nullable();
            $table->string('foto_veiculo', 200)->nullable();
            $table->string('versao_veiculo', 30)->nullable();
            $table->unsignedInteger('usuario_id')->nullable()->index('veiculos_usuario_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('veiculos');
    }
};
