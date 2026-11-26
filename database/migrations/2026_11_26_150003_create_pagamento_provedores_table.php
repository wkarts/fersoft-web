<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pagamento_provedores')) {
            Schema::create('pagamento_provedores', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id');
                $table->unsignedBigInteger('local');
                $table->string('provedor', 40);
                $table->timestamps();
                $table->unique(['empresa_id', 'local', 'provedor'], 'pag_provedor_estabelecimento');
            });
        }
        // As duas credenciais Asaas de um estabelecimento passam a pertencer ao mesmo cadastro.
        DB::table('pagamento_conexoes')->select('empresa_id', 'local')->distinct()->get()->each(function ($site) {
            DB::table('pagamento_provedores')->insertOrIgnore([
                'empresa_id' => $site->empresa_id, 'local' => $site->local, 'provedor' => 'asaas',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamento_provedores');
    }
};
