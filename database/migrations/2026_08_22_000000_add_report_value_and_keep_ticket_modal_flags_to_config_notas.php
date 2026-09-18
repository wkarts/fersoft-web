<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('config_notas', 'pesagem_exibir_valores_relatorio')) {
            Schema::table('config_notas', function (Blueprint $table) {
                $table->boolean('pesagem_exibir_valores_relatorio')
                    ->default(true)
                    ->after('pesagem_exibir_chave_pix_relatorio');
            });
        }

        if (!Schema::hasColumn('config_notas', 'pesagem_manter_modal_ticket_aberto_apos_salvar')) {
            Schema::table('config_notas', function (Blueprint $table) {
                $table->boolean('pesagem_manter_modal_ticket_aberto_apos_salvar')
                    ->default(false)
                    ->after('pesagem_exibir_valores_relatorio');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('config_notas', 'pesagem_manter_modal_ticket_aberto_apos_salvar')) {
            Schema::table('config_notas', function (Blueprint $table) {
                $table->dropColumn('pesagem_manter_modal_ticket_aberto_apos_salvar');
            });
        }

        if (Schema::hasColumn('config_notas', 'pesagem_exibir_valores_relatorio')) {
            Schema::table('config_notas', function (Blueprint $table) {
                $table->dropColumn('pesagem_exibir_valores_relatorio');
            });
        }
    }
};
