<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pagamento_conexoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            // Zero representa a matriz nesta chave única (MySQL admite múltiplos NULL).
            $table->unsignedBigInteger('local')->default(0);
            $table->string('ambiente', 12);
            $table->boolean('selecionada')->nullable();
            $table->uuid('webhook_id')->unique();
            $table->string('carteira');
            $table->unsignedBigInteger('conta_bancaria_id')->nullable();
            $table->text('credencial');
            $table->text('segredo');
            $table->timestamps();
            $table->unique(['empresa_id', 'local', 'ambiente']);
            $table->unique(['empresa_id', 'local', 'selecionada']);
            $table->unique(['ambiente', 'carteira']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamento_conexoes');
    }
};
