<?php

namespace App\Http\Controllers;

use App\Models\{Empresa, PagamentoCobranca, PagamentoConexao};
use App\Services\Pagamentos\{Cobrancas, Estabelecimento};
use Argws\ElectronicPayments\{ChargeId, Environment, PaymentMethod};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PagamentoEletronicoController extends Controller
{
    public function __construct(private Cobrancas $service) {}

    private function cobranca(Estabelecimento $site, string $referencia): PagamentoCobranca
    {
        return PagamentoCobranca::whereIn('conexao_id', $site->conexoes()->select('id'))
            ->where('referencia', $referencia)->firstOrFail();
    }

    public function index(Request $request)
    {
        $site = Estabelecimento::atual();
        $conexoes = $site->conexoes()->get();
        $ativa = $conexoes->firstWhere('selecionada', true);
        $fiscal = Estabelecimento::fiscal($site->empresa, $site->local);
        $titulo = $request->filled('titulo') && $ativa
            ? $this->service->titulo($ativa, $request->integer('titulo'))->firstOrFail() : null;
        $cobrancas = PagamentoCobranca::with('conexao')->whereIn('conexao_id', $conexoes->pluck('id'))
            ->latest('id')->paginate(30);
        return view('pagamentos.index', compact('site', 'conexoes', 'ativa', 'fiscal', 'titulo', 'cobrancas'))
            ->with('title', 'Pagamentos eletrônicos');
    }

    public function configurar(Request $request)
    {
        $site = Estabelecimento::atual(true);
        abort_unless($request->input('estabelecimento') === $site->empresa.'-'.$site->local, 409, 'O estabelecimento mudou. Reabra a configuração.');
        $key = $request->input('credencial');
        $secret = $request->input('segredo');
        $request->request->remove('credencial');
        $request->request->remove('segredo');
        $request->validate(['ambiente' => 'required|in:sandbox,production', 'ativar' => 'nullable|boolean']);
        abort_if(($key !== null && (!is_string($key) || strlen($key) > 4096)) ||
            ($secret !== null && (!is_string($secret) || strlen($secret) < 32 || strlen($secret) > 255)), 422,
            'Confira a chave e o token de autenticação do webhook (32 a 255 caracteres).');
        $env = $request->input('ambiente');
        $c = $site->conexoes()->firstOrNew(['ambiente' => $env], [
            'empresa_id' => $site->empresa, 'local' => $site->local, 'webhook_id' => (string) Str::uuid(),
        ]);
        $c->account()->authorize(Estabelecimento::fiscal($site->empresa, $site->local));
        abort_if(!$c->exists && (!$key || !$secret), 422, 'Informe a chave e o token do webhook.');
        if ($key) {
            $c->credencial = $key;
            try {
                $wallets = $this->service->connection($c)->sdk()->accountInfo->retrieveWalletid();
            } catch (\Exception $e) {
                abort(422, 'O Asaas não confirmou a carteira. Confira a credencial, o ambiente e a permissão de leitura da conta.');
            }
            $wallet = $wallets['data'][0]['id'] ?? null;
            abort_unless(is_string($wallet) && $wallet !== '' && count($wallets['data']) === 1 && empty($wallets['hasMore']), 422,
                'A chave precisa identificar uma única carteira Asaas.');
            abort_if($c->exists && $c->carteira !== $wallet, 409, 'A nova chave pertence a uma carteira diferente.');
            $c->carteira = $wallet;
        }
        if ($secret) {
            $c->segredo = $secret;
        }
        DB::transaction(function () use ($site, $request, $c) {
            Empresa::whereKey($site->empresa)->lockForUpdate()->firstOrFail();
            if ($request->boolean('ativar')) {
                $site->conexoes()->where('ambiente', '<>', $c->ambiente)->where('selecionada', true)->update(['selecionada' => null]);
                $c->selecionada = true;
            } else {
                $c->selecionada = null;
            }
            $c->save();
        });
        return redirect()->route('pagamentos.index')->with('mensagem_sucesso', 'Configuração do estabelecimento salva.');
    }

    public function emitir(Request $request)
    {
        $site = Estabelecimento::atual();
        $dados = $request->validate([
            'metodo' => 'required|in:pix,boleto,credit_card', 'titulo' => 'required|integer|min:1',
        ]);
        $p = $this->service->emitir($site, PaymentMethod::from($dados['metodo']), (int) $dados['titulo'], (string) $request->input('conexao'));
        return redirect()->route('pagamentos.detalhe', $p->referencia);
    }

    public function pdv(Request $request)
    {
        $site = Estabelecimento::atual();
        $dados = $request->validate(['metodo' => 'required|in:pix,boleto,credit_card', 'cliente' => 'required|integer|min:1',
            'itens' => 'required|array|min:1', 'itens.*.id' => 'required|integer|min:1',
            'itens.*.quantidade' => 'required', 'itens.*.valor' => 'required', 'operacao' => 'required|uuid', 'centavos' => 'required|integer|between:1,99999999999999']);
        $p = $this->service->emitir($site, PaymentMethod::from($dados['metodo']), null, (string) $request->input('conexao'), $dados);
        return response()->json(['referencia' => $p->referencia, 'url' => route('pagamentos.detalhe', $p->referencia),
            'ambiente' => $p->conexao->ambiente]);
    }

    public function liberarPdv(Request $request)
    {
        $site = Estabelecimento::atual();
        $dados = $request->validate(['operacao' => 'required|uuid', 'conexao' => 'required|uuid']);
        $c = $site->conexoes()->where('webhook_id', $dados['conexao'])->firstOrFail();
        DB::transaction(function () use ($site, $c, $dados) {
            PagamentoConexao::lockForUpdate()->findOrFail($c->id);
            $p = PagamentoCobranca::where('conexao_id', $c->id)->where('origem', 'pdv-'.$site->usuario->id.'-'.$dados['operacao'])
                ->latest('id')->lockForUpdate()->first();
            abort_if($p && $c->ambiente !== 'sandbox' && $p->vigente && !$p->venda_caixa_id, 409,
                'Conclua a venda ou cancele a cobrança de produção antes de liberar o caixa.');
        });
        return response()->noContent();
    }

    public function detalhe(string $referencia)
    {
        $p = $this->cobranca(Estabelecimento::atual(), $referencia);
        $api = $this->service->provider($p->conexao);
        $remote = null;
        $instrucoes = [];
        if ($p->externo && $p->vigente && $p->conciliacao !== 'review') {
            $id = new ChargeId($p->conexao->account(), 'asaas', $p->externo);
            $remote = $api->get($id);
            if ($remote->reference === $p->referencia && $remote->amountCents == $p->centavos &&
                in_array($remote->status->value, ['pending', 'overdue'], true)) {
                $instrucoes = match ($p->metodo) {
                    'pix' => $api->pixInstructions($id),
                    'boleto' => $api->bankSlipInstructions($id),
                    default => [],
                };
            } else {
                $remote = null;
            }
        }
        return view('pagamentos.detalhe', compact('p', 'remote', 'instrucoes'))->with('title', 'Cobrança eletrônica');
    }

    public function sincronizar(string $referencia)
    {
        $p = $this->cobranca(Estabelecimento::atual(), $referencia);
        $this->service->sincronizar($p);
        return redirect()->route('pagamentos.detalhe', $referencia);
    }

    public function alterar(Request $request, string $referencia)
    {
        $site = Estabelecimento::atual(true);
        $request->validate(['acao' => 'required|in:cancelar,estornar']);
        $p = $this->cobranca($site, $referencia);
        $remote = $this->service->sincronizar($p);
        $p->refresh();
        $cancelar = $request->acao === 'cancelar';
        abort_unless($remote && $p->conciliacao !== 'review' && in_array($remote->status->value,
            $cancelar ? ['pending', 'overdue'] : ['confirmed', 'settled'], true), 409, 'Situação incompatível com esta operação.');
        abort_unless(PagamentoCobranca::whereKey($p->id)->whereNull('acao_pendente')
            ->where('situacao', $remote->status->value)->update(['acao_pendente' => $request->acao]), 409,
            'Já existe uma operação em andamento. Confira a cobrança no Asaas.');
        $api = $this->service->provider($p->conexao);
        if ($cancelar) {
            $api->cancel($remote->id);
            $p->update(['vigente' => null, 'situacao' => 'cancelled', 'conciliacao' => $p->conexao->ambiente === 'sandbox' ? 'simulation' : 'cancelled']);
        } elseif ($p->metodo === 'boleto') {
            $p->update(['url_estorno' => $api->requestBankSlipRefund($remote->id)]);
        } else {
            $api->refund($remote->id);
            $this->service->sincronizar($p);
        }
        return redirect()->route('pagamentos.detalhe', $referencia);
    }

    public function relatorio(Request $request)
    {
        $c = Estabelecimento::atual(true)->ativa();
        $reports = $this->service->provider($c)->reports();
        $catalogo = collect($reports->catalog())->keyBy('id');
        $id = (string) $request->query('relatorio', 'statement');
        abort_unless($catalogo->has($id), 404);
        $def = $catalogo[$id];
        $filters = $request->input('filtros', []);
        abort_unless(is_array($filters), 422);
        if ($request->boolean('csv')) {
            $file = $reports->export($id, $filters);
            return response()->streamDownload(function () use ($file) {
                try { fpassthru($file->stream()); } finally { $file->close(); }
            }, $file->filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
        }
        $dados = $request->boolean('consultar') ? $reports->page($id, $filters)['report'] : null;
        $contas = $id === 'statement' && $c->ambiente === 'production'
            ? \App\Models\ContaEmpresa::withoutGlobalScopes()->withoutDeleted()->where('empresa_id', $c->empresa_id)->where('filial_id', $c->local ?: null)
                ->where('status', true)->when($c->conta_bancaria_id, fn ($q) => $q->whereKey($c->conta_bancaria_id))->pluck('nome', 'id')
            : [];
        return view('pagamentos.relatorio', compact('contas', 'c', 'catalogo', 'def', 'dados', 'filters'))->with('title', 'Relatórios Asaas');
    }

    public function importar(Request $request, \App\Services\Pagamentos\ImportarExtrato $extrato)
    {
        $site = Estabelecimento::atual(true);
        $c = $site->ativa();
        abort_unless($c->webhook_id === $request->input('conexao'), 409, 'Reabra o extrato no estabelecimento atual.');
        $dados = $request->validate(['conta' => 'required|integer|min:1', 'inicio' => 'required|date_format:Y-m-d',
            'fim' => 'required|date_format:Y-m-d|after_or_equal:inicio']);
        abort_if((new \DateTimeImmutable($dados['inicio']))->diff(new \DateTimeImmutable($dados['fim']))->days > 30, 422,
            'Selecione um período de até 31 dias.');
        $total = $extrato->executar($site, $c, $dados['conta'], $dados['inicio'], $dados['fim']);
        return redirect()->route('pagamentos.relatorio')->with('mensagem_sucesso', "$total lançamento(s) novo(s) na conciliação bancária.");
    }

    public function webhook(Request $request, string $chave)
    {
        $c = PagamentoConexao::where('webhook_id', $chave)->firstOrFail();
        try {
            $event = $this->service->provider($c)->webhook($request->getContent(),
                (string) $request->header('asaas-access-token'), $c->segredo);
        } catch (\DomainException $e) {
            return response()->json([], 403);
        } catch (\JsonException|\UnexpectedValueException|\InvalidArgumentException $e) {
            return response()->json([], 422);
        }
        if ($event) {
            $p = PagamentoCobranca::where('conexao_id', $c->id)->where(function ($q) use ($event) {
                $q->where('externo', $event->charge->id);
                if ($event->reference) { $q->orWhere('referencia', $event->reference); }
            })->first();
            if ($p && !$this->service->evento($p, $event)) {
                return response()->json([], 503);
            }
        }
        return response()->noContent();
    }
}
