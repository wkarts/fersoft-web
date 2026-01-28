<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Lotes
        Schema::create('nfse_lotes', function (Blueprint $t) {
            $t->id();

            $t->unsignedInteger('empresa_id');
            $t->unsignedInteger('filial_id')->nullable();
            $t->unsignedInteger('usuario_id')->nullable(); // último usuário que editou/criou

            $t->string('provider', 40)->index(); // 'ssa'
            $t->unsignedBigInteger('numero_lote')->index();

            $t->string('cnpj', 14)->index();
            $t->string('inscricao_municipal', 25)->nullable();

            $t->unsignedInteger('quantidade_rps')->default(0);
            $t->string('situacao', 20)->default('pendente'); // pendente|enviado|processado|erro
            $t->string('protocolo', 60)->nullable()->index();

            $t->longText('xml_envio')->nullable();
            $t->longText('xml_retorno')->nullable();
            $t->json('mensagens')->nullable();

            $t->timestamps();

            $t->unique(['empresa_id','filial_id','numero_lote','provider'], 'uk_nfse_lote_emp_fil_num');

            $t->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $t->foreign('filial_id')->references('id')->on('filials')->onDelete('set null');
            $t->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
        });

        // RPS
        Schema::create('nfse_rps', function (Blueprint $t) {
            $t->id();

            $t->unsignedInteger('empresa_id');
            $t->unsignedInteger('filial_id')->nullable();
            $t->unsignedInteger('usuario_id')->nullable();
            $t->unsignedBigInteger('nfse_lote_id');

            $t->string('numero_rps', 15)->index();
            $t->string('serie', 5)->nullable();
            $t->date('data_emissao');

            $t->string('natureza_operacao', 2)->default('1'); // 1..6
            $t->boolean('optante_simples')->default(false);
            $t->boolean('incentivador_cultural')->default(false);

            // Prestador
            $t->string('prestador_cnpj', 14);
            $t->string('prestador_im', 25)->nullable();

            // Tomador
            $t->string('tomador_cnpjcpf', 14)->nullable();
            $t->string('tomador_razao', 115)->nullable();
            $t->string('tomador_email', 80)->nullable();
            $t->string('tomador_municipio_ibge', 7)->nullable();
            $t->string('tomador_uf', 2)->nullable();
            $t->string('tomador_cep', 8)->nullable();
            $t->string('tomador_logradouro', 125)->nullable();
            $t->string('tomador_numero', 10)->nullable();
            $t->string('tomador_bairro', 60)->nullable();
            $t->string('tomador_complemento', 60)->nullable();

            // Serviço
            $t->string('item_lista_servico', 6)->nullable();
            $t->decimal('aliquota', 5, 4)->nullable();
            $t->decimal('valor_servicos', 15, 2);
            $t->decimal('valor_deducoes', 15, 2)->default(0);
            $t->decimal('valor_pis', 15, 2)->default(0);
            $t->decimal('valor_cofins', 15, 2)->default(0);
            $t->decimal('valor_inss', 15, 2)->default(0);
            $t->decimal('valor_ir', 15, 2)->default(0);
            $t->decimal('valor_csll', 15, 2)->default(0);
            $t->decimal('valor_iss', 15, 2)->default(0);
            $t->boolean('iss_retido')->default(false);
            $t->string('discriminacao', 4000)->nullable();
            $t->string('codigo_municipio_prestacao', 7)->nullable();

            $t->longText('xml_rps')->nullable();
            $t->json('mensagens')->nullable();

            $t->timestamps();

            $t->unique(['empresa_id','filial_id','numero_rps','serie'], 'uk_nfse_rps_emp_fil_num_serie');

            $t->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $t->foreign('filial_id')->references('id')->on('filials')->onDelete('set null');
            $t->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
            $t->foreign('nfse_lote_id')->references('id')->on('nfse_lotes')->onDelete('cascade');
        });

        // Notas
        Schema::create('nfse_notas', function (Blueprint $t) {
            $t->id();

            $t->unsignedInteger('empresa_id');
            $t->unsignedInteger('filial_id')->nullable();
            $t->unsignedInteger('usuario_id')->nullable();

            $t->string('provider', 40)->index();
            $t->string('numero_nfse', 15)->index();
            $t->date('data_emissao')->nullable();
            $t->string('codigo_verificacao', 20)->nullable();
            $t->string('protocolo', 60)->nullable();
            $t->string('situacao', 20)->default('ativa'); // ativa|cancelada|substituida

            $t->longText('xml_comp_nfse')->nullable();
            $t->json('mensagens')->nullable();

            $t->timestamps();

            $t->unique(['empresa_id','filial_id','numero_nfse','provider'], 'uk_nfse_notas_emp_fil_num');

            $t->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $t->foreign('filial_id')->references('id')->on('filials')->onDelete('set null');
            $t->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfse_notas');
        Schema::dropIfExists('nfse_rps');
        Schema::dropIfExists('nfse_lotes');
    }
};
