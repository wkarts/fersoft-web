<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\Pagamentos\{AcessoFinanceiro, Estabelecimento};
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ParametroPagamentoController extends Controller
{
    private function administrador(): Usuario
    {
        return Usuario::where('empresa_id', session('user_logged.empresa'))->findOrFail(session('user_logged.id'));
    }

    public function index(Request $request)
    {
        $usuario = $this->administrador();
        $locais = Estabelecimento::locaisNaCentral($usuario);
        $request->validate(['local' => 'sometimes|required|integer|min:0', 'provedor' => 'sometimes|required|string',
            'ambiente' => 'sometimes|required|in:sandbox,production']);
        $padrao = max(0, (int) $usuario->local_padrao);
        $local = $request->integer('local', isset($locais[$padrao]) ? $padrao : (int) array_key_first($locais));
        abort_unless(isset($locais[$local]), 403, 'Nenhum estabelecimento autorizado para esta configuração.');
        $site = new Estabelecimento((int) $usuario->empresa_id, $local, $usuario);
        $catalogo = config('pagamentos_eletronicos.provedores');
        $provedores = $site->provedores()->orderBy('provedor')->get()->keyBy('provedor');
        $conexoes = \App\Models\PagamentoConexao::where('empresa_id', $site->empresa)->where('local', $local)->get()->groupBy('provedor');
        $fiscal = Estabelecimento::fiscal($site->empresa, $local)->value;
        $ambientes = ['sandbox' => 'Homologação'];
        if ($fiscal === 'production') {
            $ambientes['production'] = 'Produção';
        }
        $codigo = $request->query('provedor');
        $ambiente = $request->query('ambiente', 'sandbox');
        abort_unless(isset($ambientes[$ambiente]), 403, 'Produção indisponível para este estabelecimento.');
        abort_if($codigo !== null && (!isset($catalogo[$codigo]) || !$provedores->has($codigo)), 404);

        return view('pagamentos.parametros.estabelecimento', compact('locais', 'local', 'site', 'catalogo', 'provedores',
            'conexoes', 'ambientes', 'ambiente', 'codigo'))->with('title', 'Configuração de pagamentos eletrônicos');
    }

    private function estabelecimento(Request $request): Estabelecimento
    {
        if ($request->route('local') !== null) {
            $usuario = $this->administrador();
            $local = (int) $request->route('local');
            abort_unless(isset(Estabelecimento::locaisNaCentral($usuario)[$local]), 403);
            $site = new Estabelecimento((int) $usuario->empresa_id, $local, $usuario);
        } else {
            $site = Estabelecimento::paraParametros((int) $request->route('filial'));
            abort_unless($site, 403);
        }
        abort_unless($request->isMethod('GET') || $request->input('estabelecimento') === $site->empresa.'-'.$site->local, 409,
            'O estabelecimento mudou. Reabra o cadastro.');
        return $site;
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
        return redirect()->route('configuracao-pagamentos.acessos', ['local' => $site->local, 'usuario' => $usuario->id])
            ->with('mensagem_sucesso', 'Acessos do usuário atualizados neste estabelecimento.');
    }

    public function adicionar(Request $request)
    {
        $site = $this->estabelecimento($request);
        $provedor = $request->input('provedor');
        abort_unless(is_string($provedor) && isset(config('pagamentos_eletronicos.provedores')[$provedor]), 422,
            'Selecione um provedor disponível.');
        $site->provedores()->firstOrCreate(['empresa_id' => $site->empresa, 'local' => $site->local, 'provedor' => $provedor]);
        return redirect()->route('configuracao-pagamentos.index', ['local' => $site->local, 'provedor' => $provedor])
            ->with('mensagem_sucesso', 'Provedor adicionado ao estabelecimento.');
    }

    public function salvar(Request $request)
    {
        $site = $this->estabelecimento($request);
        $provedor = $request->route('provedor');
        $definicao = config('pagamentos_eletronicos.provedores')[$provedor] ?? null;
        abort_unless($definicao, 404);
        $cadastro = $site->provedores()->where('provedor', $provedor)->firstOrFail();
        app($definicao['parametros'])->salvar($site, $cadastro, $request);
        return redirect()->route('configuracao-pagamentos.index', ['local' => $site->local, 'provedor' => $provedor,
            'ambiente' => $request->input('ambiente')])->with('mensagem_sucesso', 'Parâmetros de pagamento salvos.');
    }
}
