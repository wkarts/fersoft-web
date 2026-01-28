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
        Schema::create('conta_bancarias', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('conta_bancarias_empresa_id_foreign');
            $table->string('banco', 30);
            $table->string('agencia', 10);
            $table->string('conta', 15);
            $table->string('titular', 45);
            $table->boolean('padrao')->default(false);
            $table->boolean('usar_logo')->default(false);
            $table->string('cnpj', 18);
            $table->string('endereco', 50);
            $table->string('cep', 9);
            $table->string('bairro', 30);
            $table->unsignedInteger('cidade_id')->index('conta_bancarias_cidade_id_foreign');
            $table->string('carteira', 10)->default('');
            $table->string('convenio', 20)->default('');
            $table->decimal('juros', 10)->default(0);
            $table->decimal('multa', 10)->default(0);
            $table->integer('juros_apos')->default(0);
            $table->string('tipo', 7);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conta_bancarias');
    }
};
