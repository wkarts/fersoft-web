<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pagamento_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cobranca_id')->constrained('pagamento_cobrancas');
            $table->string('evento_id');
            $table->string('tipo');
            $table->timestamp('processado_em');
            $table->unique(['cobranca_id', 'evento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamento_eventos');
    }
};
