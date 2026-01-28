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
        Schema::create('boletos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('banco_id')->index('boletos_banco_id_foreign');
            $table->unsignedInteger('conta_id')->index('boletos_conta_id_foreign');
            $table->string('numero', 10);
            $table->string('numero_documento', 10);
            $table->string('carteira', 10);
            $table->string('convenio', 20);
            $table->string('linha_digitavel', 50);
            $table->string('nome_arquivo', 40);
            $table->decimal('juros', 10);
            $table->decimal('multa', 10);
            $table->integer('juros_apos');
            $table->string('instrucoes', 100);
            $table->string('tipo', 7);
            $table->boolean('logo')->default(false);
            $table->string('posto', 10)->default('');
            $table->string('codigo_cliente', 10)->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boletos');
    }
};
