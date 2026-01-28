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
        Schema::create('tributacao_ufs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produto_id')->index('tributacao_ufs_produto_id_foreign');
            $table->string('uf', 2);
            $table->decimal('percentual_icms', 5);
            $table->decimal('percentual_fcp', 5);
            $table->decimal('percentual_icms_interno', 5);
            $table->decimal('percentual_red_bc', 5)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tributacao_ufs');
    }
};
