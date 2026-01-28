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
        Schema::table('veiculos', function (Blueprint $table) {
            $table->unsignedInteger('filial_id')->nullable()->after('empresa_id')->index('veiculos_filial_id_foreign');
            $table->softDeletes();
        });

        Schema::table('manutencoes', function (Blueprint $table) {
            $table->unsignedInteger('filial_id')->nullable()->after('empresa_id')->index('manutencoes_filial_id_foreign');
            $table->softDeletes();
        });

        Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
            $table->unsignedInteger('filial_id')->nullable()->after('empresa_id')->index('movimentacoes_veiculos_filial_id_foreign');
            $table->softDeletes();
        });

        Schema::table('tipos_movimentacoes', function (Blueprint $table) {
            $table->unsignedInteger('filial_id')->nullable()->after('empresa_id')->index('tipos_movimentacoes_filial_id_foreign');
            $table->softDeletes();
        });

        Schema::table('veiculos', function (Blueprint $table) {
            $table->foreign('filial_id')
                ->references('id')
                ->on('filials')
                ->onUpdate('no action')
                ->onDelete('set null');
        });

        Schema::table('manutencoes', function (Blueprint $table) {
            $table->foreign('filial_id')
                ->references('id')
                ->on('filials')
                ->onUpdate('no action')
                ->onDelete('set null');
        });

        Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
            $table->foreign('filial_id')
                ->references('id')
                ->on('filials')
                ->onUpdate('no action')
                ->onDelete('set null');
        });

        Schema::table('tipos_movimentacoes', function (Blueprint $table) {
            $table->foreign('filial_id')
                ->references('id')
                ->on('filials')
                ->onUpdate('no action')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipos_movimentacoes', function (Blueprint $table) {
            $table->dropForeign('tipos_movimentacoes_filial_id_foreign');
            $table->dropColumn('filial_id');
            $table->dropSoftDeletes();
        });

        Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
            $table->dropForeign('movimentacoes_veiculos_filial_id_foreign');
            $table->dropColumn('filial_id');
            $table->dropSoftDeletes();
        });

        Schema::table('manutencoes', function (Blueprint $table) {
            $table->dropForeign('manutencoes_filial_id_foreign');
            $table->dropColumn('filial_id');
            $table->dropSoftDeletes();
        });

        Schema::table('veiculos', function (Blueprint $table) {
            $table->dropForeign('veiculos_filial_id_foreign');
            $table->dropColumn('filial_id');
            $table->dropSoftDeletes();
        });
    }
};
