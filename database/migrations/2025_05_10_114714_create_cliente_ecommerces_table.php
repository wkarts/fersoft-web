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
        Schema::create('cliente_ecommerces', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('cliente_ecommerces_empresa_id_foreign');
            $table->string('nome', 30);
            $table->string('sobre_nome', 30);
            $table->string('cpf', 18);
            $table->string('ie', 15)->default('');
            $table->string('email', 60);
            $table->string('telefone', 15);
            $table->string('senha', 100);
            $table->string('token', 20)->default('');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_ecommerces');
    }
};
