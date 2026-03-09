<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('fornecedors', 'tipo_pix')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql') {
                DB::statement("
                    ALTER TABLE fornecedors
                    CHANGE COLUMN tipo_pix tipo_pix
                    ENUM('cpf','cnpj','email','telefone','chave aleatória')
                    NOT NULL DEFAULT 'cpf'
                ");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fornecedors', 'tipo_pix')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql') {
                DB::statement("
                    UPDATE fornecedors
                    SET tipo_pix = 'cpf'
                    WHERE tipo_pix = 'chave aleatória'
                ");

                DB::statement("
                    ALTER TABLE fornecedors
                    CHANGE COLUMN tipo_pix tipo_pix
                    ENUM('cpf','cnpj','email','telefone')
                    NOT NULL DEFAULT 'cpf'
                ");
            }
        }
    }
};
