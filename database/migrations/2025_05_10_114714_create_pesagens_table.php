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
        Schema::create('pesagens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id')->index();
            $table->unsignedInteger('usuario_id')->index();
            $table->unsignedInteger('veiculo_id')->index();
            $table->unsignedInteger('motorista_id')->nullable()->index();
            $table->unsignedInteger('venda_id')->nullable()->index('pesagens_venda_id_foreign');
            $table->unsignedInteger('compra_id')->nullable()->index('pesagens_compra_id_foreign');
            $table->decimal('peso', 18, 6);
            $table->decimal('peso_liquido_bruto', 18, 6)->default(0);
            $table->decimal('peso_final', 18, 6)->default(0);
            $table->string('placa_veiculo', 100)->nullable();
            $table->string('placa_carreta', 100)->nullable();
            $table->string('motorista_nome', 300)->nullable();
            $table->string('nf_numero', 100)->nullable();
            $table->decimal('nf_peso', 18, 6)->nullable();
            $table->date('nf_data')->nullable();
            $table->timestamp('dt_entrada')->nullable();
            $table->timestamp('dt_saida')->nullable();
            $table->enum('status', ['em andamento', 'concluído'])->default('em andamento');
            $table->enum('tipo', ['compra', 'venda'])->default('compra');
            $table->unsignedInteger('cliente_id')->nullable();
            $table->unsignedInteger('fornecedor_id')->nullable()->index('pesagens_fornecedor_id_foreign');
            $table->date('dt_registro');
            $table->boolean('danificado')->default(false);
            $table->boolean('quebrado')->default(false);
            $table->boolean('esverdeado')->default(false);
            $table->boolean('ardido')->default(false);
            $table->boolean('secagem')->default(false);
            $table->decimal('umidade_desconto', 18, 6)->default(0);
            $table->decimal('impureza_desconto', 18, 6)->default(0);
            $table->decimal('danificado_desconto', 18, 6)->default(0);
            $table->decimal('quebrado_desconto', 18, 6)->default(0);
            $table->decimal('esverdeado_desconto', 18, 6)->default(0);
            $table->decimal('ardido_desconto', 18, 6)->default(0);
            $table->decimal('secagem_desconto', 18, 6)->default(0);
            $table->text('observacoes')->nullable();
            $table->boolean('view_public')->default(false);
            $table->string('token', 100)->unique();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['cliente_id', 'fornecedor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesagens');
    }
};
