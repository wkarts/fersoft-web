
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
        Schema::table('vendas', function (Blueprint $table) {
            $table->foreign(['cliente_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['filial_id'])->references(['id'])->on('filials')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['frete_id'])->references(['id'])->on('fretes')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['natureza_id'])->references(['id'])->on('natureza_operacaos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['transportadora_id'])->references(['id'])->on('transportadoras')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['usuario_id'])->references(['id'])->on('usuarios')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->dropForeign('vendas_cliente_id_foreign');
            $table->dropForeign('vendas_empresa_id_foreign');
            $table->dropForeign('vendas_filial_id_foreign');
            $table->dropForeign('vendas_frete_id_foreign');
            $table->dropForeign('vendas_natureza_id_foreign');
            $table->dropForeign('vendas_transportadora_id_foreign');
            $table->dropForeign('vendas_usuario_id_foreign');
        });
    }
};
