<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MergeProdutosPreservingOriginalBase extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('produtos')) {
            return;
        }

        Schema::table('produtos', function (Blueprint $table) {
            if (!Schema::hasColumn('produtos', 'ca_numero')) {
                $table->string('ca_numero', 30)
                    ->nullable()
                    ->after('nome');
            }

            if (!Schema::hasColumn('produtos', 'fabricante')) {
                $table->string('fabricante', 100)
                    ->nullable()
                    ->after('ca_numero');
            }

            if (!Schema::hasColumn('produtos', 'tipo_item')) {
                $table->string('tipo_item', 2)
                    ->default('07')
                    ->after('fabricante');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('produtos')) {
            return;
        }

        Schema::table('produtos', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('produtos', 'tipo_item')) {
                $columnsToDrop[] = 'tipo_item';
            }

            if (Schema::hasColumn('produtos', 'fabricante')) {
                $columnsToDrop[] = 'fabricante';
            }

            if (Schema::hasColumn('produtos', 'ca_numero')) {
                $columnsToDrop[] = 'ca_numero';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
}
