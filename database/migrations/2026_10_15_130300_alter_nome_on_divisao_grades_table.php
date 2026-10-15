<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Increase the division name limit to 100 characters.
     */
    public function up(): void
    {
        Schema::table('divisao_grades', function (Blueprint $table) {
            $table->string('nome', 100)->nullable(false)->change();
        });
    }

    /**
     * Restore the original limit without truncating existing data.
     */
    public function down(): void
    {
        foreach (DB::table('divisao_grades')
            ->select(['id', 'nome'])
            ->orderBy('id')
            ->cursor() as $divisao) {
            if (mb_strlen((string) $divisao->nome, 'UTF-8') > 30) {
                throw new \RuntimeException(
                    'Rollback cancelado: divisao_grades contém nome com mais de 30 caracteres.'
                );
            }
        }

        Schema::table('divisao_grades', function (Blueprint $table) {
            $table->string('nome', 30)->nullable(false)->change();
        });
    }
};
