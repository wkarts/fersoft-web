<?php

namespace App\Services\Pagamentos;

use App\Models\{BankStatementTransaction, ContaEmpresa, PagamentoConexao};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportarExtrato
{
    public function __construct(private Cobrancas $cobrancas) {}

    public function executar(Estabelecimento $site, PagamentoConexao $c, int $contaId, string $inicio, string $fim): int
    {
        abort_unless($c->ambiente === 'production', 409, 'Somente extratos de produção podem entrar na conciliação bancária.');
        $conta = ContaEmpresa::withoutGlobalScopes()->withoutDeleted()->where('empresa_id', $site->empresa)->where('filial_id', $site->local ?: null)
            ->where('status', true)->findOrFail($contaId);
        $reports = $this->cobrancas->provider($c)->reports();
        $offset = 0;
        $inseridos = 0;
        do {
            $page = $reports->page('statement', ['date_from' => $inicio, 'date_to' => $fim, 'order' => 'asc',
                'per_page' => 100, 'offset' => $offset])['report'];
            $inseridos += DB::transaction(function () use ($site, $conta, $c, $page, $inicio, $fim) {
                $locked = PagamentoConexao::lockForUpdate()->findOrFail($c->id);
                $locked->account()->authorize(Estabelecimento::fiscal($site->empresa, $site->local));
                abort_if($locked->conta_bancaria_id && $locked->conta_bancaria_id != $conta->id, 409,
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
                if ($page['rows'] && !$locked->conta_bancaria_id) {
                    $locked->update(['conta_bancaria_id' => $conta->id]);
                }
                return $novos;
            });
            $offset = $page['next_offset'];
        } while ($offset !== null);
        return $inseridos;
    }
}
