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
        Schema::create('cliente_uploads', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cliente_id')->index('cliente_uploads_cliente_id_foreign');
            $table->string('file_name', 100);
            $table->string('estensao', 10);
            $table->string('descricao', 200)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_uploads');
    }
};
