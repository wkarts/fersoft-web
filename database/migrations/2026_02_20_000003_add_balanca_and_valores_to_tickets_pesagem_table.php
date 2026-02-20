<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tickets_pesagem')) {
            return;
        }

        Schema::table('tickets_pesagem', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets_pesagem', 'balanca_config_id')) {
                $table->unsignedInteger('balanca_config_id')
                    ->nullable()
                    ->after('motorista_id')
                    ->index('tickets_pesagem_balanca_config_id_index');
            }

            if (!Schema::hasColumn('tickets_pesagem', 'peso_origem')) {
                $table->string('peso_origem', 30)
                    ->default('manual')
                    ->after('peso_bag');
            }

            if (!Schema::hasColumn('tickets_pesagem', 'valor_unitario')) {
                $table->decimal('valor_unitario', 18, 6)
                    ->default(0)
                    ->after('peso_origem');
            }

            if (!Schema::hasColumn('tickets_pesagem', 'valor_total')) {
                $table->decimal('valor_total', 18, 6)
                    ->default(0)
                    ->after('valor_unitario');
            }

            if (!Schema::hasColumn('tickets_pesagem', 'valor_origem')) {
                $table->string('valor_origem', 30)
                    ->default('manual')
                    ->after('valor_total');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tickets_pesagem')) {
            return;
        }

        Schema::table('tickets_pesagem', function (Blueprint $table) {
            foreach (['balanca_config_id', 'peso_origem', 'valor_unitario', 'valor_total', 'valor_origem'] as $column) {
                if (Schema::hasColumn('tickets_pesagem', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
