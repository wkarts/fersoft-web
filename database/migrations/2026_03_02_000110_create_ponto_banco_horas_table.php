<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ponto_banco_horas')) {
            Schema::create('ponto_banco_horas', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id')->index();
                $table->unsignedInteger('funcionario_id')->index();
                $table->date('data_referencia')->index();
                $table->integer('minutos_credito')->default(0);
                $table->integer('minutos_debito')->default(0);
                $table->integer('saldo_minutos')->default(0);
                $table->string('origem', 60)->nullable();
                $table->date('expira_em')->nullable();
                $table->text('observacoes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ponto_banco_horas')) {
            Schema::drop('ponto_banco_horas');
        }
    }
};
