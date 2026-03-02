<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ponto_ajuste_aprovacoes')) {
            Schema::create('ponto_ajuste_aprovacoes', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id')->index();
                $table->unsignedInteger('ponto_ajuste_id')->index();
                $table->unsignedInteger('aprovador_id')->index();
                $table->string('status', 40);
                $table->text('parecer')->nullable();
                $table->unsignedTinyInteger('nivel')->default(1);
                $table->timestamp('aprovado_em')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ponto_ajuste_aprovacoes')) {
            Schema::drop('ponto_ajuste_aprovacoes');
        }
    }
};
