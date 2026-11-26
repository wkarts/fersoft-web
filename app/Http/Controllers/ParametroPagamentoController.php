<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\Pagamentos\{AcessoFinanceiro, Estabelecimento};
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ParametroPagamentoController extends Controller
{
    private function estabelecimento(Request $request): Estabelecimento
    {
        $site = Estabelecimento::paraParametros((int) $request->route('filial'));
        abort_unless($site, 403);
        abort_unless($request->isMethod('GET') || $request->input('estabelecimento') === $site->empresa.'-'.$site->local, 409,
            'O estabelecimento mudou. Reabra o cadastro.');
        return $site;
    }

    private function voltar(Estabelecimento $site, string $mensagem)
    {
        $url = $site->local ? route('filial.edit', $site->local) : url('/configNF');
        return redirect($url.'#pagamentos-eletronicos')->with('mensagem_sucesso', $mensagem)
            ->with('abrir_parametros_pagamento', true);
    }

    public function acessos(Request $request)
    {
        $site = $this->estabelecimento($request);
        $provedor = $site->provedores()->where('provedor', 'asaas')->firstOrFail();
        $usuarios = Usuario::where('empresa_id', $site->empresa)->orderBy('nome')->pluck('nome', 'id');
        $usuario = $request->integer('usuario');
        abort_if($usuario && !$usuarios->has($usuario), 404);
        $salvos = DB::table('pagamento_acessos')->where('provedor_id', $provedor->id)->where('usuario_id', $usuario)->value('recursos');
        return view('pagamentos.parametros.acessos', compact('site', 'usuarios', 'usuario'))
            ->with('ativos', json_decode($salvos ?? '[]', true))->with('opcoes', AcessoFinanceiro::opcoes())
            ->with('title', 'Acessos financeiros por usuário');
    }

    public function salvarAcessos(Request $request)
    {
        $site = $this->estabelecimento($request);
        $provedor = $site->provedores()->where('provedor', 'asaas')->firstOrFail();
        $usuario = Usuario::where('empresa_id', $site->empresa)->findOrFail($request->route('usuario'));
        $dados = $request->validate(['recursos' => 'sometimes|array', 'recursos.*' => ['string', 'distinct',
            \Illuminate\Validation\Rule::in(array_keys(AcessoFinanceiro::opcoes()))]]);
        DB::table('pagamento_acessos')->upsert([['provedor_id' => $provedor->id, 'usuario_id' => $usuario->id,
            'recursos' => json_encode(array_values($dados['recursos'] ?? []), JSON_THROW_ON_ERROR)]],
            ['provedor_id', 'usuario_id'], ['recursos']);
        return redirect()->route('parametros-pagamentos.'.($site->local ? 'filial' : 'emitente').'.acessos',
            ($site->local ? ['filial' => $site->local] : []) + ['usuario' => $usuario->id])
            ->with('mensagem_sucesso', 'Acessos do usuário atualizados neste estabelecimento.');
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
