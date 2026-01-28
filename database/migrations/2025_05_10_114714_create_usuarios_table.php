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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome');
            $table->string('login');
            $table->boolean('adm');
            $table->string('senha');
            $table->string('email', 200);
            $table->string('img', 100)->default('');
            $table->boolean('ativo');
            $table->boolean('somente_fiscal')->default(true);
            $table->boolean('caixa_livre')->default(false);
            $table->boolean('permite_desconto')->default(true);
            $table->boolean('menu_representante')->default(false);
            $table->text('permissao');
            $table->unsignedInteger('empresa_id')->index('usuarios_empresa_id_foreign');
            $table->integer('tema')->default(1);
            $table->integer('tema_menu')->default(1);
            $table->string('tipo_menu', 20)->default('lateral');
            $table->string('rota_acesso', 150)->nullable();
            $table->text('locais')->nullable();
            $table->integer('local_padrao')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
