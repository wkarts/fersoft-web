<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private string $tabela = 'pagamento_conexoes';
    private array $chaves = [
        'pag_conexao_ambiente' => [['empresa_id', 'local', 'ambiente'], ['empresa_id', 'local', 'provedor', 'ambiente']],
        'pag_conexao_selecao' => [['empresa_id', 'local', 'selecionada'], ['empresa_id', 'local', 'provedor', 'selecionada']],
        'pag_conexao_carteira' => [['ambiente', 'carteira'], ['provedor', 'ambiente', 'carteira']],
    ];

    public function up(): void
    {
        if (!Schema::hasColumn($this->tabela, 'provedor')) {
            Schema::table($this->tabela, fn (Blueprint $t) => $t->string('provedor', 40)->default('asaas'));
        }
        foreach ($this->chaves as $nome => [$antes, $depois]) {
            $this->trocarIndice($this->tabela.'_'.implode('_', $antes).'_unique', $nome, $depois);
        }
        if (!collect(Schema::getForeignKeys($this->tabela))->contains('foreign_table', 'pagamento_provedores')) {
            Schema::table($this->tabela, fn (Blueprint $t) => $t->foreign(['empresa_id', 'local', 'provedor'])
                ->references(['empresa_id', 'local', 'provedor'])->on('pagamento_provedores'));
        }
    }

    public function down(): void
    {
        // Restaura a unicidade anterior antes de remover o vínculo. Dados incompatíveis impedem o rollback.
        foreach ($this->chaves as [$antes, $depois]) {
            $nome = $this->tabela.'_'.implode('_', $antes).'_unique';
            if (!Schema::hasIndex($this->tabela, $nome)) {
                Schema::table($this->tabela, fn (Blueprint $t) => $t->unique($antes, $nome));
            }
        }
        if (collect(Schema::getForeignKeys($this->tabela))->contains('foreign_table', 'pagamento_provedores')) {
            Schema::table($this->tabela, fn (Blueprint $t) => $t->dropForeign(['empresa_id', 'local', 'provedor']));
        }
        foreach (array_keys($this->chaves) as $nome) {
            if (Schema::hasIndex($this->tabela, $nome)) {
                Schema::table($this->tabela, fn (Blueprint $t) => $t->dropUnique($nome));
            }
        }
        Schema::table($this->tabela, fn (Blueprint $t) => $t->dropColumn('provedor'));
    }

    private function trocarIndice(string $antigo, string $novo, array $colunas): void
    {
        if (!Schema::hasIndex($this->tabela, $novo)) {
            Schema::table($this->tabela, fn (Blueprint $t) => $t->unique($colunas, $novo));
        }
        if (Schema::hasIndex($this->tabela, $antigo)) {
            Schema::table($this->tabela, fn (Blueprint $t) => $t->dropUnique($antigo));
        }
    }
};
