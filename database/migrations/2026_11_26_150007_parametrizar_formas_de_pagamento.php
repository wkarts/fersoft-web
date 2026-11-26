<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pagamento_conexoes', function (Blueprint $t) {
            $t->json('metodos')->nullable();
            $t->unsignedInteger('max_parcelas_cartao')->default(1);
        });
        Schema::table('pagamento_cobrancas', function (Blueprint $t) {
            $t->unsignedInteger('numero_parcelas')->default(1);
            $t->string('parcelamento_externo')->nullable();
            $t->unique(['conexao_id', 'parcelamento_externo'], 'pag_conexao_parcelamento');
        });
    }

    public function down(): void
    {
        Schema::table('pagamento_cobrancas', fn (Blueprint $t) => $t->dropUnique('pag_conexao_parcelamento'));
        Schema::table('pagamento_cobrancas', fn (Blueprint $t) => $t->dropColumn(['numero_parcelas', 'parcelamento_externo']));
        Schema::table('pagamento_conexoes', fn (Blueprint $t) => $t->dropColumn(['metodos', 'max_parcelas_cartao']));
    }
};
