<?php

namespace App\Http\Controllers;

use App\Services\Pagamentos\Estabelecimento;
use Illuminate\Http\Request;

class ParametroPagamentoController extends Controller
{
    private function estabelecimento(Request $request): Estabelecimento
    {
        $site = Estabelecimento::paraParametros((int) $request->route('filial'));
        abort_unless($site, 403);
        abort_unless($request->input('estabelecimento') === $site->empresa.'-'.$site->local, 409,
            'O estabelecimento mudou. Reabra o cadastro.');
        return $site;
    }

    private function voltar(Estabelecimento $site, string $mensagem)
    {
        $url = $site->local ? route('filial.edit', $site->local) : url('/configNF');
        return redirect($url.'#pagamentos-eletronicos')->with('mensagem_sucesso', $mensagem)
            ->with('abrir_parametros_pagamento', true);
    }

    public function adicionar(Request $request)
    {
        $site = $this->estabelecimento($request);
        $provedor = $request->input('provedor');
        abort_unless(is_string($provedor) && isset(config('pagamentos_eletronicos.provedores')[$provedor]), 422,
            'Selecione um provedor disponível.');
        $site->provedores()->firstOrCreate(['empresa_id' => $site->empresa, 'local' => $site->local, 'provedor' => $provedor]);
        return $this->voltar($site, 'Provedor disponível na grade deste estabelecimento.');
    }

    public function salvar(Request $request)
    {
        $site = $this->estabelecimento($request);
        $provedor = $request->route('provedor');
        $definicao = config('pagamentos_eletronicos.provedores')[$provedor] ?? null;
        abort_unless($definicao, 404);
        $cadastro = $site->provedores()->where('provedor', $provedor)->firstOrFail();
        app($definicao['parametros'])->salvar($site, $cadastro, $request);
        return $this->voltar($site, 'Parâmetros de pagamento salvos.');
    }
}
