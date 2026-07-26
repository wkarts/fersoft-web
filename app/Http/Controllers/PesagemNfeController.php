<?php

namespace App\Http\Controllers;

use App\Helpers\StockMove;
use App\Models\CategoriaConta;
use App\Models\Compra;
use App\Models\ContaEmpresa;
use App\Models\ContaPagar;
use App\Models\Fornecedor;
use App\Models\ItemCompra;
use App\Models\ItemContaEmpresa;
use App\Models\NaturezaOperacao;
use App\Models\Pesagem;
use App\Models\Produto;
use App\Models\TabelaPrecoItem;
use App\Models\TabelaPrecoNfe;
use App\Utils\ContaEmpresaUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PesagemNfeController extends BaseController
{
    protected $redirectPage = '/pesagemNfe';
    protected $formTitle = 'Emissão de NF-e de Pesagens';

    public function __construct(private readonly ContaEmpresaUtil $contaEmpresaUtil)
    {
        parent::__construct();
    }

    protected function rules(): array
    {
        return [];
    }

    protected function messages(): array
    {
        return [];
    }

    public function index(Request $request)
    {
        $dataInicio = $this->normalizarData($request->input('data_inicio'), now()->startOfMonth()->toDateString());
        $dataFim = $this->normalizarData($request->input('data_fim'), now()->endOfMonth()->toDateString());
        if ($dataInicio > $dataFim) {
            [$dataInicio, $dataFim] = [$dataFim, $dataInicio];
        }

        $statusPesagem = in_array($request->input('status_pesagem'), ['pendentes', 'emitidas', 'todos'], true)
            ? $request->input('status_pesagem')
            : 'pendentes';
        $fornecedorId = $request->filled('fornecedor_id') && $request->input('fornecedor_id') !== 'todos'
            ? (int) $request->input('fornecedor_id')
            : null;
        $filialFiltro = $request->input('filial_id', 'todos');

        $pesagensQuery = Pesagem::query()
            ->leftJoin('fornecedors', function ($join): void {
                $join->on('pesagens.fornecedor_id', '=', 'fornecedors.id')
                    ->on('pesagens.empresa_id', '=', 'fornecedors.empresa_id');
            })
            ->leftJoin('compras', function ($join): void {
                $join->on('pesagens.compra_id', '=', 'compras.id')
                    ->on('pesagens.empresa_id', '=', 'compras.empresa_id');
            })
            ->where('pesagens.empresa_id', $this->empresa_id)
            ->whereBetween(DB::raw('DATE(COALESCE(pesagens.dt_registro, pesagens.created_at))'), [$dataInicio, $dataFim])
            ->whereIn('pesagens.status', ['concluído', 'concluido', 'CONCLUÍDO', 'CONCLUIDO']);

        if ($fornecedorId) {
            $pesagensQuery->where('pesagens.fornecedor_id', $fornecedorId);
        }

        if ($filialFiltro === 'matriz') {
            $pesagensQuery->whereNull('pesagens.filial_id');
        } elseif (is_numeric($filialFiltro) && (int) $filialFiltro > 0) {
            $pesagensQuery->where('pesagens.filial_id', (int) $filialFiltro);
        }

        if ($statusPesagem === 'pendentes') {
            $pesagensQuery->whereNull('pesagens.compra_id');
        } elseif ($statusPesagem === 'emitidas') {
            $pesagensQuery->whereNotNull('pesagens.compra_id');
        }

        $pesagens = $pesagensQuery
            ->select([
                'pesagens.*',
                'fornecedors.razao_social as fornecedor_nome',
                'compras.numero_emissao as nfe_numero',
                'compras.estado as nfe_estado',
                'compras.valor as nfe_valor',
            ])
            ->orderByDesc('pesagens.id')
            ->paginate(50)
            ->appends($request->query());

        $fornecedores = Fornecedor::query()
            ->where('empresa_id', $this->empresa_id)
            ->when(DB::getSchemaBuilder()->hasColumn('fornecedors', 'ativo'), fn ($q) => $q->where('ativo', 1))
            ->orderBy('razao_social')
            ->get();
        $contas = ContaEmpresa::query()
            ->where('empresa_id', $this->empresa_id)
            ->where('status', 1)
            ->orderBy('nome')
            ->get();
        $naturezas = NaturezaOperacao::query()
            ->where('empresa_id', $this->empresa_id)
            ->orderBy('descricao')
            ->get();
        $categorias = CategoriaConta::query()
            ->where('empresa_id', $this->empresa_id)
            ->where('tipo', 'pagar')
            ->orderBy('nome')
            ->get();
        $filiais = DB::table('filials')->where('empresa_id', $this->empresa_id)->orderBy('descricao')->get();

        return view('pesagem_nfe.index', [
            'pesagens' => $pesagens,
            'fornecedores' => $fornecedores,
            'contas' => $contas,
            'naturezas' => $naturezas,
            'categorias' => $categorias,
            'filiais' => $filiais,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'status_pesagem' => $statusPesagem,
            'fornecedor_id' => $fornecedorId ?: 'todos',
            'filial_id' => $filialFiltro,
            'title' => $this->formTitle,
        ]);
    }

    public function processar(Request $request)
    {
        $dados = $request->validate([
            'pesagens' => ['required', 'array', 'min:1'],
            'pesagens.*' => ['integer'],
            'natureza_id' => ['required', 'integer'],
            'tipo_pagamento_nfe' => ['required', 'string', 'max:3'],
            'data_emissao' => ['required', 'date'],
            'data_pagamento' => ['nullable', 'date'],
            'integrar_financeiro' => ['nullable'],
            'pagamento_a_vista' => ['nullable'],
            'conta_id' => ['nullable', 'integer'],
            'categoria_id' => ['nullable', 'integer'],
        ]);

        $natureza = NaturezaOperacao::query()
            ->where('empresa_id', $this->empresa_id)
            ->findOrFail((int) $dados['natureza_id']);

        $integrarFinanceiro = $request->boolean('integrar_financeiro');
        $pagamentoAVista = $request->boolean('pagamento_a_vista');
        $dataEmissao = Carbon::parse($dados['data_emissao']);
        $dataPagamento = !empty($dados['data_pagamento'])
            ? Carbon::parse($dados['data_pagamento'])
            : $dataEmissao->copy();
        $isAVistaNfe = $pagamentoAVista
            || $dataEmissao->toDateString() === $dataPagamento->toDateString()
            || $dados['tipo_pagamento_nfe'] === '01';

        $conta = null;
        $categoria = null;
        if ($integrarFinanceiro) {
            $categoria = CategoriaConta::query()
                ->where('empresa_id', $this->empresa_id)
                ->where('tipo', 'pagar')
                ->findOrFail((int) ($dados['categoria_id'] ?? 0));

            if ($pagamentoAVista) {
                $conta = ContaEmpresa::query()
                    ->where('empresa_id', $this->empresa_id)
                    ->where('status', 1)
                    ->findOrFail((int) ($dados['conta_id'] ?? 0));
            }
        }

        $resumo = ['sucesso' => 0, 'erros' => []];

        foreach (array_unique(array_map('intval', $dados['pesagens'])) as $pesagemId) {
            $processamento = null;
            try {
                $processamento = DB::transaction(function () use (
                    $pesagemId,
                    $natureza,
                    $dados,
                    $integrarFinanceiro,
                    $pagamentoAVista,
                    $dataEmissao,
                    $dataPagamento,
                    $conta,
                    $categoria
                ): array {
                    $pesagem = Pesagem::query()
                        ->where('empresa_id', $this->empresa_id)
                        ->lockForUpdate()
                        ->findOrFail($pesagemId);

                    if ($pesagem->compra_id) {
                        throw new \RuntimeException("A pesagem já foi processada na compra #{$pesagem->compra_id}.");
                    }
                    if (!in_array(mb_strtolower((string) $pesagem->status), ['concluído', 'concluido'], true)) {
                        throw new \RuntimeException('Somente pesagens concluídas podem gerar NF-e de entrada.');
                    }

                    $fornecedor = Fornecedor::query()
                        ->where('empresa_id', $this->empresa_id)
                        ->findOrFail((int) $pesagem->fornecedor_id);
                    $this->validarFornecedor($fornecedor);

                    $tickets = DB::table('tickets_pesagem')
                        ->where('pesagem_id', $pesagem->id)
                        ->orderBy('id')
                        ->get();
                    if ($tickets->isEmpty()) {
                        throw new \RuntimeException('A pesagem não possui tickets registrados.');
                    }

                    $itens = $this->agruparItens($tickets, $fornecedor->id, $pesagem->filial_id);
                    if (!$itens) {
                        throw new \RuntimeException('Nenhum item válido foi encontrado nos tickets da pesagem.');
                    }

                    $valorTotal = array_sum(array_column($itens, 'valor_total_pagar'));
                    $pesoTotalNfe = array_sum(array_column($itens, 'quantidade_nfe'));

                    $compra = Compra::create([
                        'empresa_id' => $this->empresa_id,
                        'filial_id' => $pesagem->filial_id ?: null,
                        'fornecedor_id' => $fornecedor->id,
                        'usuario_id' => $this->usuario_id,
                        'valor' => round($valorTotal, 2),
                        'estado' => 'NOVO',
                        'observacao' => 'Ref. Pesagem #' . $pesagem->id,
                        'natureza_id' => $natureza->id,
                        'tipo_pagamento' => $dados['tipo_pagamento_nfe'],
                        'data_emissao' => $dataEmissao->format('Y-m-d H:i:s'),
                        'data_retroativa' => $dataEmissao->toDateString(),
                        'peso_liquido' => round($pesoTotalNfe, 3),
                        'peso_bruto' => round($pesoTotalNfe, 3),
                    ]);

                    $stockMove = app(StockMove::class);
                    foreach ($itens as $item) {
                        ItemCompra::create([
                            'compra_id' => $compra->id,
                            'produto_id' => $item['produto_id'],
                            'quantidade' => round($item['quantidade_nfe'], 4),
                            'valor_unitario' => round($item['preco_nfe'], 4),
                            'unidade_compra' => $item['unidade_compra'],
                        ]);

                        $stockMove->pluStock(
                            $item['produto_id'],
                            $item['quantidade_nfe'],
                            $item['preco_nfe'],
                            $pesagem->filial_id,
                            'PesagemNfe',
                            $pesagem->id,
                            $dataEmissao->toDateString()
                        );
                    }

                    $contaPagarId = null;
                    if ($integrarFinanceiro) {
                        $contaPagar = ContaPagar::create([
                            'empresa_id' => $this->empresa_id,
                            'filial_id' => $pesagem->filial_id ?: null,
                            'usuario_id' => $this->usuario_id,
                            'fornecedor_id' => $fornecedor->id,
                            'compra_id' => $compra->id,
                            'valor_integral' => round($valorTotal, 2),
                            'valor_original' => round($valorTotal, 2),
                            'valor_pago' => $pagamentoAVista ? round($valorTotal, 2) : 0,
                            'data_emissao' => $dataEmissao->toDateString(),
                            'data_emissao_nfe' => $dataEmissao->toDateString(),
                            'data_vencimento' => $dataPagamento->toDateString(),
                            'data_pagamento' => $pagamentoAVista ? $dataPagamento->toDateString() : null,
                            'status' => $pagamentoAVista ? 1 : 0,
                            'referencia' => 'Ref. Ticket Pesagem Nº ' . $pesagem->id,
                            'categoria_id' => $categoria?->id,
                            'tipo_pagamento' => $dados['tipo_pagamento_nfe'],
                        ]);
                        $contaPagarId = $contaPagar->id;

                        if ($pagamentoAVista) {
                            $itemConta = ItemContaEmpresa::create([
                                'empresa_id' => $this->empresa_id,
                                'conta_id' => $conta->id,
                                'valor' => round($valorTotal, 2),
                                'tipo' => 'saida',
                                'data_pagamento' => $dataPagamento->toDateString(),
                                'descricao' => "Pgto Ref. Ticket Pesagem Nº {$pesagem->id}",
                                'conta_pagar_id' => $contaPagar->id,
                                'categoria_id' => $categoria?->id,
                                'user_id' => $this->usuario_id,
                                'origem' => 'NF-e de Pesagem',
                            ]);
                            $this->contaEmpresaUtil->atualizaSaldo($itemConta);
                        }
                    }

                    $pesagem->compra_id = $compra->id;
                    $pesagem->save();

                    return [
                        'compra_id' => $compra->id,
                        'conta_pagar_id' => $contaPagarId,
                    ];
                }, 3);

                $contaDesvinculada = false;
                try {
                    if ($isAVistaNfe && $processamento['conta_pagar_id']) {
                        DB::table('conta_pagars')
                            ->where('empresa_id', $this->empresa_id)
                            ->where('id', $processamento['conta_pagar_id'])
                            ->update(['compra_id' => null]);
                        $contaDesvinculada = true;
                    }

                    $response = Http::timeout(120)
                        ->withHeaders([
                            'Cookie' => (string) request()->header('cookie'),
                            'X-CSRF-TOKEN' => csrf_token(),
                        ])
                        ->asForm()
                        ->post(url('/compras/gerarEntrada'), [
                            'compra_id' => $processamento['compra_id'],
                            'natureza' => $natureza->id,
                            'tipo_pagamento' => $dados['tipo_pagamento_nfe'],
                        ]);
                } finally {
                    if ($contaDesvinculada) {
                        DB::table('conta_pagars')
                            ->where('empresa_id', $this->empresa_id)
                            ->where('id', $processamento['conta_pagar_id'])
                            ->update(['compra_id' => $processamento['compra_id']]);
                    }
                }

                if ($response->successful() && $response->json('sucesso')) {
                    $resumo['sucesso']++;
                } else {
                    $mensagem = $response->json('mensagem') ?: $response->body();
                    $resumo['erros'][] = "Pesagem #{$pesagemId} integrada, porém a geração fiscal retornou erro: {$mensagem}";
                }
            } catch (\Throwable $e) {
                Log::error('Erro ao gerar NF-e a partir da pesagem', [
                    'empresa_id' => $this->empresa_id,
                    'pesagem_id' => $pesagemId,
                    'erro' => $e->getMessage(),
                ]);
                $resumo['erros'][] = "Pesagem #{$pesagemId}: {$e->getMessage()}";
            }
        }

        return redirect()->back()->with('resumo', $resumo);
    }

    private function agruparItens($tickets, int $fornecedorId, ?int $filialId): array
    {
        $itens = [];

        foreach ($tickets as $ticket) {
            $produtoOriginal = Produto::query()
                ->where('empresa_id', $this->empresa_id)
                ->find((int) $ticket->produto_id);
            if (!$produtoOriginal) {
                continue;
            }

            $produtoFinalId = (int) ($produtoOriginal->produto_referenciado_id ?: $produtoOriginal->id);
            $produtoFinal = Produto::query()
                ->where('empresa_id', $this->empresa_id)
                ->find($produtoFinalId);
            if (!$produtoFinal) {
                continue;
            }

            $peso = max(0, (float) ($ticket->peso ?? 0) - (float) ($ticket->peso_bag ?? 0));
            if ($peso <= 0) {
                continue;
            }

            $precoFornecedor = (float) TabelaPrecoItem::query()
                ->where('produto_id', $produtoOriginal->id)
                ->when($fornecedorId, function ($q) use ($fornecedorId): void {
                    if (DB::getSchemaBuilder()->hasColumn('tabela_preco_itens', 'fornecedor_id')) {
                        $q->where(function ($sub) use ($fornecedorId): void {
                            $sub->where('fornecedor_id', $fornecedorId)->orWhereNull('fornecedor_id');
                        });
                    }
                })
                ->orderByDesc('id')
                ->value('valor_kg');
            if ($precoFornecedor <= 0) {
                $precoFornecedor = (float) ($produtoOriginal->valor_compra ?? 0);
            }

            $precoNfe = (float) TabelaPrecoNfe::query()
                ->where('empresa_id', $this->empresa_id)
                ->where('produto_id', $produtoFinalId)
                ->where(function ($q) use ($filialId): void {
                    if ($filialId) {
                        $q->where('filial_id', $filialId)->orWhereNull('filial_id');
                    } else {
                        $q->whereNull('filial_id');
                    }
                })
                ->orderByRaw('filial_id IS NULL')
                ->value('preco_nfe');
            if ($precoNfe <= 0) {
                $precoNfe = $precoFornecedor > 0 ? $precoFornecedor : (float) ($produtoFinal->valor_compra ?? 0);
            }
            if ($precoNfe <= 0) {
                throw new \RuntimeException("Informe o preço da pauta NF-e para o produto {$produtoFinal->nome}.");
            }

            $valorPagar = $peso * $precoFornecedor;
            $quantidadeNfe = $precoFornecedor > 0 ? ($valorPagar / $precoNfe) : $peso;
            if ($quantidadeNfe <= 0) {
                $quantidadeNfe = $peso;
            }

            if (!isset($itens[$produtoFinalId])) {
                $itens[$produtoFinalId] = [
                    'produto_id' => $produtoFinalId,
                    'quantidade_nfe' => 0.0,
                    'preco_nfe' => $precoNfe,
                    'valor_total_pagar' => 0.0,
                    'unidade_compra' => $produtoFinal->unidade_compra ?: 'KG',
                ];
            }

            $itens[$produtoFinalId]['quantidade_nfe'] += $quantidadeNfe;
            $itens[$produtoFinalId]['valor_total_pagar'] += $valorPagar;
        }

        return array_values($itens);
    }

    private function validarFornecedor(Fornecedor $fornecedor): void
    {
        $erros = [];
        $documento = preg_replace('/\D/', '', (string) $fornecedor->cpf_cnpj);
        if (!in_array(strlen($documento), [11, 14], true)) {
            $erros[] = 'CPF/CNPJ inválido';
        }
        foreach (['rua' => 'endereço', 'numero' => 'número', 'bairro' => 'bairro', 'cidade_id' => 'cidade'] as $campo => $rotulo) {
            if (empty($fornecedor->{$campo})) {
                $erros[] = $rotulo;
            }
        }
        $cep = preg_replace('/\D/', '', (string) $fornecedor->cep);
        if (strlen($cep) !== 8) {
            $erros[] = 'CEP';
        }
        if ($erros) {
            throw new \RuntimeException('Cadastro do fornecedor incompleto: ' . implode(', ', $erros) . '.');
        }
    }

    private function normalizarData($data, string $padrao): string
    {
        try {
            return $data ? Carbon::parse($data)->toDateString() : $padrao;
        } catch (\Throwable) {
            return $padrao;
        }
    }
}
