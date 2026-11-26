<?php

namespace App\Http\Controllers;

use App\Models\{PagamentoCobranca, PagamentoConexao};
use App\Services\Pagamentos\{AcessoFinanceiro, Cobrancas, Estabelecimento};
use Argws\ElectronicPayments\{ChargeId, PaymentMethod};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $fiscal = Estabelecimento::fiscal($site->empresa, $site->local);
        $ativa = $conexoes->first(fn ($c) => $c->selecionada &&
            ($c->ambiente === 'sandbox' || $fiscal->value === 'production'));
        $titulo = $request->filled('titulo') && $ativa
            ? $this->service->titulo($ativa, $request->integer('titulo'))->firstOrFail() : null;
        $cobrancas = PagamentoCobranca::with('conexao')->whereIn('conexao_id', $conexoes->pluck('id'))
            ->latest('id')->paginate(30);
        $acessos = AcessoFinanceiro::doUsuario($site);
        $temRelatorio = (bool) array_intersect($acessos, array_column(\Argws\ElectronicPayments\Asaas\Reports\AsaasReportCatalog::publicDefinitions(), 'id'));
        return view('pagamentos.index', compact('temRelatorio', 'site', 'conexoes', 'ativa', 'fiscal', 'titulo', 'cobrancas'))
            ->with('title', 'Pagamentos eletrônicos');
    }

    public function emitir(Request $request)
    {
        $site = Estabelecimento::atual();
        $dados = $request->validate([
            'numero_parcelas' => 'sometimes|integer|min:1', 'metodo' => 'required|in:pix,boleto,credit_card', 'titulo' => 'required|integer|min:1',
        ]);
        $p = $this->service->emitir($site, PaymentMethod::from($dados['metodo']), (int) $dados['titulo'], (string) $request->input('conexao'), numeroParcelas: (int) ($dados['numero_parcelas'] ?? 1));
        return redirect()->route('pagamentos.detalhe', $p->referencia);
    }

    public function pdv(Request $request)
    {
        $site = Estabelecimento::atual();
        $dados = $request->validate(['numero_parcelas' => 'sometimes|integer|min:1', 'metodo' => 'required|in:pix,boleto,credit_card', 'cliente' => 'required|integer|min:1',
            'total' => 'required|integer|between:1,99999999999999', 'parcela' => 'nullable|integer|min:0',
            'parcelas' => 'nullable|array', 'parcelas.*.tipo' => 'required|string', 'parcelas.*.valor' => 'required',
            'itens' => 'required|array|min:1', 'itens.*.id' => 'required|integer|min:1',
            'itens.*.quantidade' => 'required', 'itens.*.valor' => 'required', 'operacao' => 'required|uuid', 'centavos' => 'required|integer|between:1,99999999999999']);
        $p = $this->service->emitir($site, PaymentMethod::from($dados['metodo']), null, (string) $request->input('conexao'), $dados, (int) ($dados['numero_parcelas'] ?? 1));
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
        $site = Estabelecimento::atual();
        $acessos = AcessoFinanceiro::doUsuario($site);
        $p = $this->cobranca($site, $referencia);
        $api = $this->service->provider($p->conexao);
        $remote = null;
        $instrucoes = [];
        if ($p->externo && $p->vigente && $p->conciliacao !== 'review') {
            $id = new ChargeId($p->conexao->account(), 'asaas', $p->externo);
            $remote = $api->get($id);
            if ($p->numero_parcelas > 1) {
                $grupo = \App\Services\Pagamentos\ParcelamentoCartao::conferir($p, $remote, $this->service, null);
                $remote = $grupo[0] ?? null;
            }
            if ($remote && $remote->reference === $p->referencia && $remote->amountCents == $p->centavos &&
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
        return view('pagamentos.detalhe', compact('p', 'remote', 'instrucoes', 'acessos'))->with('title', 'Cobrança eletrônica');
    }

    public function sincronizar(string $referencia)
    {
        $site = Estabelecimento::atual();
        abort_unless(in_array('sincronizar', AcessoFinanceiro::doUsuario($site), true), 403);
        $p = $this->cobranca($site, $referencia);
        $this->service->sincronizar($p);
        return redirect()->route('pagamentos.detalhe', $referencia);
    }

    public function alterar(Request $request, string $referencia)
    {
        $site = Estabelecimento::atual();
        $request->validate(['acao' => 'required|in:cancelar,estornar']);
        abort_unless(in_array($request->acao, AcessoFinanceiro::doUsuario($site), true), 403);
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
        if ($p->numero_parcelas > 1) {
            $parcelamento = $this->service->connection($p->conexao)->sdk()->installment;
            $path = ['id' => $remote->installmentId];
            if ($cancelar) {
                $parcelamento->cancelChargesOfAnInstallment($path);
            } else {
                $parcelamento->refundInstallment($path);
            }
            $this->service->sincronizar($p);
        } elseif ($cancelar) {
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
        $site = Estabelecimento::atual();
        $c = $site->ativa();
        $reports = $this->service->provider($c)->reports();
        $acessos = AcessoFinanceiro::doUsuario($site);
        $catalogo = collect($reports->catalog())->keyBy('id')->only($acessos);
        $id = $request->query('relatorio', $catalogo->keys()->first());
        abort_unless(is_string($id) && $catalogo->has($id), 403);
        $podeCsv = AcessoFinanceiro::permiteCsv($id, $acessos);
        $podeImportar = in_array('importar', $acessos, true) && in_array('statement', $acessos, true);
        $def = $catalogo[$id];
        $filters = $request->input('filtros', []);
        abort_unless(is_array($filters), 422);
        if ($request->boolean('csv')) {
            abort_unless($podeCsv, 403);
            $file = $reports->export($id, $filters);
            return response()->streamDownload(function () use ($file) {
                try { fpassthru($file->stream()); } finally { $file->close(); }
            }, $file->filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
        }
        $dados = $request->boolean('consultar') ? $reports->page($id, $filters)['report'] : null;
        if ($dados && $id === 'statement' && !in_array('balance', $acessos, true)) {
            $dados['columns'] = array_filter($dados['columns'], fn ($col) => $col['key'] !== 'balance');
            foreach ($dados['rows'] as &$linha) {
                unset($linha['balance']);
            }
            unset($linha);
        }
        $conta = $podeImportar && $id === 'statement' && $c->ambiente === 'production' && $c->conta_bancaria_id
            ? $site->contasBancarias()->whereKey($c->conta_bancaria_id)->value('nome') : null;
        return view('pagamentos.relatorio', compact('podeCsv', 'podeImportar', 'conta', 'c', 'catalogo', 'def', 'dados', 'filters'))->with('title', 'Relatórios Asaas');
    }

    public function importar(Request $request, \App\Services\Pagamentos\ImportarExtrato $extrato)
    {
        $site = Estabelecimento::atual();
        $acessos = AcessoFinanceiro::doUsuario($site);
        abort_unless(in_array('importar', $acessos, true) && in_array('statement', $acessos, true), 403);
        $c = $site->ativa();
        abort_unless($c->webhook_id === $request->input('conexao'), 409, 'Reabra o extrato no estabelecimento atual.');
        $dados = $request->validate(['inicio' => 'required|date_format:Y-m-d',
            'fim' => 'required|date_format:Y-m-d|after_or_equal:inicio']);
        abort_if((new \DateTimeImmutable($dados['inicio']))->diff(new \DateTimeImmutable($dados['fim']))->days > 30, 422,
            'Selecione um período de até 31 dias.');
        $total = $extrato->executar($site, $c, $dados['inicio'], $dados['fim']);
        return redirect()->route('pagamentos.relatorio')->with('mensagem_sucesso', "$total lançamento(s) novo(s) na conciliação bancária.");
    }

    public function webhook(Request $request, string $chave)
    {
        $c = PagamentoConexao::where('provedor', 'asaas')->where('webhook_id', $chave)->first();
        if (!$c) {
            return response()->json([], 404);
        }
        if (!$c->webhook_hash) {
            return response()->json([], 403);
        }
        try {
            $event = $this->service->provider($c)->webhookWithTokenHash($request->getContent(),
                (string) $request->header('asaas-access-token'), $c->webhook_hash);
        } catch (\DomainException $e) {
            return response()->json([], 403);
        } catch (\JsonException|\UnexpectedValueException|\InvalidArgumentException $e) {
            return response()->json([], 422);
        }
        if ($event) {
            $grupo = $request->json('payment.installment');
            $p = PagamentoCobranca::where('conexao_id', $c->id)->where(function ($q) use ($event, $grupo) {
                $q->where('externo', $event->charge->id);
                if ($event->reference) { $q->orWhere('referencia', $event->reference); }
                if (is_string($grupo) && $grupo !== '') { $q->orWhere('parcelamento_externo', $grupo); }
            })->first();
            try {
                $processado = !$p || $this->service->evento($p, $event);
            } catch (\Argws\ElectronicPayments\PaymentException $e) {
                $processado = false;
            }
            if (!$processado) {
                return response()->json([], 503);
            }
        }
        return response()->noContent();
    }
}
