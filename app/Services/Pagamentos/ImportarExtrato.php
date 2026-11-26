<?php

namespace App\Services\Pagamentos;

use App\Models\{BankStatementTransaction, PagamentoConexao};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportarExtrato
{
    public function __construct(private Cobrancas $cobrancas) {}

    public function executar(Estabelecimento $site, PagamentoConexao $c, string $inicio, string $fim): int
    {
        abort_unless($c->ambiente === 'production', 409, 'Somente extratos de produção podem entrar na conciliação bancária.');
        abort_unless($c->conta_bancaria_id, 409, 'Vincule a conta bancária nos parâmetros do provedor.');
        $conta = $site->contasBancarias()->findOrFail($c->conta_bancaria_id);
        $reports = $this->cobrancas->provider($c)->reports();
        $offset = 0;
        $inseridos = 0;
        do {
            $page = $reports->page('statement', ['date_from' => $inicio, 'date_to' => $fim, 'order' => 'asc',
                'per_page' => 100, 'offset' => $offset])['report'];
            $inseridos += DB::transaction(function () use ($site, $conta, $c, $page, $inicio, $fim) {
                $locked = PagamentoConexao::lockForUpdate()->findOrFail($c->id);
                $locked->account()->authorize(Estabelecimento::fiscal($site->empresa, $site->local));
                abort_if($locked->conta_bancaria_id != $conta->id, 409,
                    'Esta carteira já está vinculada a outra conta bancária do ERP.');
                $novos = 0;
                foreach ($page['rows'] as $row) {
                    $id = $row['id'] ?? null;
                    $data = substr($row['date'] ?? '', 0, 10);
                    abort_unless(is_string($id) && $id !== '' && strlen($id) <= 100 && $data >= $inicio && $data <= $fim &&
                        isset($row['value']) && is_numeric($row['value']), 422, 'Lançamento de extrato incompleto.');
                    $registro = BankStatementTransaction::firstOrCreate([
                        'empresa_id' => $site->empresa, 'import_hash' => hash('sha256', 'asaas:'.$c->carteira.':'.$id),
                    ], [
                        'filial_id' => $site->local ?: null, 'usuario_id' => $site->usuario->id, 'conta_bancaria_id' => $conta->id,
                        'data_transacao' => $data, 'numero_documento' => $id, 'valor' => abs((float) $row['value']),
                        'tipo' => $row['value'] >= 0 ? 'credit' : 'debit', 'status' => 'pending',
                        'descricao' => Str::limit(trim(($row['type'] ?? '').' '.($row['description'] ?? '')), 250),
                    ]);
                    $novos += (int) $registro->wasRecentlyCreated;
                }
                return $novos;
            });
            $offset = $page['next_offset'];
        } while ($offset !== null);
        return $inseridos;
    }
}
