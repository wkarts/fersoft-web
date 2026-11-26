<?php

namespace App\Services\Pagamentos;

use App\Models\{PagamentoConexao, PagamentoProvedor};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ParametrosAsaas
{
    public function __construct(private Cobrancas $cobrancas) {}

    public function salvar(Estabelecimento $site, PagamentoProvedor $provedor, Request $request): void
    {
        $chave = $request->input('credencial');
        $segredo = $request->input('segredo');
        $dados = $request->only(['ambiente', 'ativar', 'conta_bancaria_id']);
        // Falhas de validação nunca devem gravar os segredos em old_input.
        abort_if(!in_array($dados['ambiente'] ?? null, ['sandbox', 'production'], true)
            || !in_array($dados['ativar'] ?? '0', ['0', '1', 0, 1], true), 422, 'Selecione um ambiente válido.');
        abort_if(($chave !== null && (!is_string($chave) || strlen($chave) > 4096))
            || ($segredo !== null && (!is_string($segredo) || strlen($segredo) < 32 || strlen($segredo) > 255)), 422,
            'Confira a chave e o token do webhook (32 a 255 caracteres).');
        $ambiente = $dados['ambiente'];
        $contaId = $dados['conta_bancaria_id'] ?? null;
        abort_if($contaId !== null && ($ambiente !== 'production' || filter_var($contaId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false), 422,
            'Selecione uma conta de produção do estabelecimento.');
        DB::transaction(function () use ($site, $provedor, $chave, $segredo, $ambiente, $contaId, $request) {
            $site->provedores()->whereKey($provedor->id)->lockForUpdate()->firstOrFail();
            $conexoes = $site->conexoes($provedor->provedor);
            $c = (clone $conexoes)->firstOrNew(['ambiente' => $ambiente], [
                'empresa_id' => $site->empresa, 'local' => $site->local, 'provedor' => $provedor->provedor,
                'webhook_id' => (string) Str::uuid(),
            ]);
            abort_if($ambiente === 'production' && Estabelecimento::fiscal($site->empresa, $site->local)->value !== 'production',
                409, 'Este estabelecimento está em homologação.');
            abort_if(!$c->exists && (!$chave || !$segredo), 422, 'Informe a chave e o token do webhook.');
            if ($contaId !== null) {
                abort_unless($site->contasBancarias()->whereKey($contaId)->exists(), 422, 'Conta bancária indisponível neste estabelecimento.');
                abort_if($c->conta_bancaria_id && $c->conta_bancaria_id != $contaId, 409, 'Esta carteira já está vinculada a outra conta bancária.');
                $c->conta_bancaria_id = $contaId;
            }
            if ($chave) {
                $c->credencial = $chave;
                $carteira = $this->carteira($c);
                abort_if($c->exists && $c->carteira !== $carteira, 409, 'A nova chave pertence a outra carteira.');
                $c->carteira = $carteira;
            }
            if ($segredo) {
                $c->segredo = $segredo;
            }
            $c->selecionada = $request->boolean('ativar') ?: null;
            if ($c->selecionada) {
                $conexoes->where('ambiente', '<>', $ambiente)->where('selecionada', true)->update(['selecionada' => null]);
            }
            $c->save();
        });
    }

    public function carteira(PagamentoConexao $conexao): string
    {
        try {
            $resposta = $this->cobrancas->connection($conexao)->sdk()->accountInfo->retrieveWalletid();
        } catch (\Exception $e) {
            abort(422, 'O Asaas não confirmou a carteira. Confira a chave, o ambiente e a permissão de leitura da conta.');
        }
        $id = $resposta['data'][0]['id'] ?? null;
        abort_unless(is_string($id) && $id !== '' && count($resposta['data']) === 1 && empty($resposta['hasMore']), 422,
            'A chave precisa identificar uma única carteira Asaas.');
        return $id;
    }
}
