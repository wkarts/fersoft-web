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
        $local = Estabelecimento::localDoTopo($usuario);
        abort_unless(isset($locais[$local]), 403, 'O estabelecimento ativo não está disponível para configuração.');
        // Um link do ambiente anterior pode ter sido recarregado ao trocar o local no topo.
        if ($request->has('local') && $request->integer('local') !== $local) {
            return redirect()->route('configuracao-pagamentos.index');
        }
        $nomeLocal = $locais[$local];
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

        return view('pagamentos.parametros.estabelecimento', compact('nomeLocal', 'local', 'site', 'catalogo', 'provedores',
            'conexoes', 'ambientes', 'ambiente', 'codigo'))->with('title', 'Configuração de pagamentos eletrônicos');
    }

    private function localDaRota(Request $request): int
    {
        return (int) ($request->route('local') ?? $request->route('filial'));
    }

    private function estabelecimento(Request $request, Usuario $usuario): Estabelecimento
    {
        $local = Estabelecimento::localDoTopo($usuario);
        abort_unless($this->localDaRota($request) === $local, 409,
            'O estabelecimento ativo mudou. Reabra a configuração antes de salvar.');
        if ($request->route('local') !== null) {
            abort_unless(isset(Estabelecimento::locaisNaCentral($usuario)[$local]), 403);
            $site = new Estabelecimento((int) $usuario->empresa_id, $local, $usuario);
        } else {
            $site = Estabelecimento::paraParametros($local);
            abort_unless($site, 403);
        }
        abort_unless($request->isMethod('GET') || $request->input('estabelecimento') === $site->empresa.'-'.$site->local, 409,
            'O estabelecimento mudou. Reabra o cadastro.');
        return $site;
    }

    public function acessos(Request $request)
    {
        $administrador = $this->administrador();
        if ($this->localDaRota($request) !== Estabelecimento::localDoTopo($administrador)) {
            return redirect()->route('configuracao-pagamentos.index');
        }
        $site = $this->estabelecimento($request, $administrador);
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
        $site = $this->estabelecimento($request, $this->administrador());
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
        $site = $this->estabelecimento($request, $this->administrador());
        $provedor = $request->input('provedor');
        abort_unless(is_string($provedor) && isset(config('pagamentos_eletronicos.provedores')[$provedor]), 422,
            'Selecione um provedor disponível.');
        $site->provedores()->firstOrCreate(['empresa_id' => $site->empresa, 'local' => $site->local, 'provedor' => $provedor]);
        return redirect()->route('configuracao-pagamentos.index', ['local' => $site->local, 'provedor' => $provedor])
            ->with('mensagem_sucesso', 'Provedor adicionado ao estabelecimento.');
    }

    public function salvar(Request $request)
    {
        $site = $this->estabelecimento($request, $this->administrador());
        $provedor = $request->route('provedor');
        $definicao = config('pagamentos_eletronicos.provedores')[$provedor] ?? null;
        abort_unless($definicao, 404);
        $cadastro = $site->provedores()->where('provedor', $provedor)->firstOrFail();
        app($definicao['parametros'])->salvar($site, $cadastro, $request);
        return redirect()->route('configuracao-pagamentos.index', ['local' => $site->local, 'provedor' => $provedor,
            'ambiente' => $request->input('ambiente')])->with('mensagem_sucesso', 'Parâmetros de pagamento salvos.');
    }
}
