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
        Schema::create('nfses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('nfses_empresa_id_foreign');
            $table->unsignedInteger('filial_id')->nullable()->index('nfses_filial_id_foreign');
            $table->decimal('valor_total', 16, 7);
            $table->enum('estado', ['novo', 'rejeitado', 'aprovado', 'cancelado', 'processando']);
            $table->string('serie', 3);
            $table->string('codigo_verificacao', 20);
            $table->integer('numero_nfse');
            $table->string('url_xml');
            $table->string('url_pdf_nfse');
            $table->string('url_pdf_rps');
            $table->unsignedInteger('cliente_id')->index('nfses_cliente_id_foreign');
            $table->string('documento', 18);
            $table->string('razao_social', 60);
            $table->string('im', 20)->nullable();
            $table->string('ie', 20)->nullable();
            $table->string('cep', 9);
            $table->string('rua', 80);
            $table->string('numero', 20);
            $table->string('bairro', 40);
            $table->string('complemento', 80)->nullable();
            $table->unsignedInteger('cidade_id')->index('nfses_cidade_id_foreign');
            $table->string('email', 80)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('natureza_operacao', 100)->nullable();
            $table->string('uuid', 100)->nullable();
            $table->string('chave', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfses');
    }
};
