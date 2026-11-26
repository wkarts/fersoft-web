<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pagamento_acessos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('provedor_id')->constrained('pagamento_provedores')->cascadeOnDelete();
            $t->unsignedBigInteger('usuario_id');
            $t->json('recursos');
            $t->unique(['provedor_id', 'usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamento_acessos');
    }
};
