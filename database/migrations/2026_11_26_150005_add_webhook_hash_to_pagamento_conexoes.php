<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Crypt, DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('pagamento_conexoes', 'webhook_hash')) {
            Schema::table('pagamento_conexoes', fn (Blueprint $t) => $t->char('webhook_hash', 64)->nullable());
        }
        if (Schema::hasIndex('pagamento_conexoes', 'pag_webhook_hash_unico')) {
            return;
        }
        DB::table('pagamento_conexoes')->select('id', 'segredo')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $token = Crypt::decryptString($row->segredo);
                DB::table('pagamento_conexoes')->where('id', $row->id)->update(['webhook_hash' => hash('sha256', $token)]);
            }
        });
        // Credenciais antigas compartilhadas exigem rotação nas conexões afetadas.
        $duplicados = DB::table('pagamento_conexoes')->groupBy('webhook_hash')->havingRaw('COUNT(*) > 1')->pluck('webhook_hash');
        foreach ($duplicados as $hash) {
            DB::table('pagamento_conexoes')->where('webhook_hash', $hash)->update(['webhook_hash' => null]);
        }
        Schema::table('pagamento_conexoes', fn (Blueprint $t) => $t->unique('webhook_hash', 'pag_webhook_hash_unico'));
    }

    public function down(): void
    {
        Schema::table('pagamento_conexoes', fn (Blueprint $t) => $t->dropUnique('pag_webhook_hash_unico'));
        Schema::table('pagamento_conexoes', fn (Blueprint $t) => $t->dropColumn('webhook_hash'));
    }
};
