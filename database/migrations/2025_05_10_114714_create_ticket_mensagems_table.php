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
        Schema::create('ticket_mensagems', function (Blueprint $table) {
            $table->increments('id');
            $table->string('imagem', 50);
            $table->text('mensagem');
            $table->unsignedInteger('ticket_id')->index('ticket_mensagems_ticket_id_foreign');
            $table->unsignedInteger('usuario_id')->index('ticket_mensagems_usuario_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_mensagems');
    }
};
