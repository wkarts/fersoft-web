<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ponto_fechamentos')) {
            Schema::create('ponto_fechamentos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id')->index();
                $table->string('competencia', 7)->index();
                $table->string('status', 40)->default('aberto');
                $table->unsignedInteger('fechado_por')->nullable()->index();
                $table->timestamp('fechado_em')->nullable();
                $table->unsignedInteger('reaberto_por')->nullable()->index();
                $table->timestamp('reaberto_em')->nullable();
                $table->unsignedInteger('versao')->default(1);
                $table->text('observacoes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ponto_fechamentos')) {
            Schema::drop('ponto_fechamentos');
        }
    }
};
