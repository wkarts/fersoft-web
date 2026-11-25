<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ProducaoLote;
use App\Models\ProducaoCorte;
use App\Models\ProducaoDestinacao;
use App\Models\Produto;
use App\Models\BalancaConfig;
use App\Models\Venda;
use App\Models\ItemVenda;
use App\Models\ConfigNota;

class ProducaoRastreabilidadeController extends Controller
{
    protected $empresa_id = null;
    protected $filial_id = null;
    protected $usuario_id = null;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $userLogged = session('user_logged');
            $this->empresa_id = $userLogged['empresa_id'] ?? $userLogged['empresa'] ?? null;
            $this->usuario_id = $userLogged['id'] ?? null;
            $this->filial_id  = $userLogged['filial_id'] ?? $userLogged['local_padrao'] ?? null;

            if (!$this->empresa_id) {
                return redirect('/login')->withErrors(['error' => 'Empresa não encontrada.']);
            }
            return $next($request);
        });
    }

    // =========================================================================
    // 👨‍💼 PAINEL DO GERENTE DE PRODUÇÃO
    // =========================================================================
    public function gerenciamentoIndex(Request $request)
    {
        $query = ProducaoLote::with(['fornecedor', 'produtoOrigem', 'itens'])
            ->where('empresa_id', $this->empresa_id);

        // Se o gerente filtrar por status ou se quisermos mostrar todos (abertos e concluídos)
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function($q) use ($term) {
                $q->where('codigo_lote', 'like', "%{$term}%")
                    ->orWhere('nfe_entrada', 'like', "%{$term}%")
                    ->orWhere('mtr_entrada', 'like', "%{$term}%");
            });
        }

        $lotes = $query->orderByDesc('id')->paginate(15);
        $title = 'Gestão de Produção & Rastreabilidade de Lotes';

        return view('producao.gerenciamento', compact('lotes', 'title'));
    }

    // Encerra o lote definitivamente e apura a quebra/perda de estoque
    public function concluirLote(Request $request, $id)
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                $lote = ProducaoLote::where('empresa_id', $this->empresa_id)->with('itens')->findOrFail($id);

                if ($lote->status === 'transformado') {
                    return response()->json(['error' => 'Este lote já foi encerrado.'], 422);
                }

                // Soma TODAS as pesagens parciais (itens) vinculadas ao lote
                $pesoProduzido = $lote->itens->sum('peso_liquido');
                $pesoEntrada   = (float) $lote->peso_entrada;

                // Calcula a perda (o que não virou produto acabado)
                $pesoPerda = max(0, $pesoEntrada - $pesoProduzido);

                // Se houver perda, realiza a BAIXA NO ESTOQUE DA MATÉRIA-PRIMA
                if ($pesoPerda > 0 && $lote->produto_origem_id) {

                    // 1. Tabela alteracao_estoques (usando 'reducao' como o seu banco exige)
                    DB::table('alteracao_estoques')->insert([
                        'empresa_id' => $this->empresa_id,
                        'produto_id' => $lote->produto_origem_id,
                        'quantidade' => $pesoPerda,
                        'tipo'       => 'reducao',
                        'observacao' => 'Baixa por Perda/Quebra Industrial - Lote: ' . $lote->codigo_lote,
                        'usuario_id' => $this->usuario_id ?? auth()->id(),
                        'created_at' => now(),
                    ]);

                    // 2. Tabela stock_movements (Auditoria com idempotency_key)
                    DB::table('stock_movements')->insert([
                        'empresa_id'      => $this->empresa_id,
                        'produto_id'      => $lote->produto_origem_id,
                        'quantidade'      => $pesoPerda,
                        'tipo'            => 'saida',
                        'contexto'        => 'perda',
                        'origem_tipo'     => 'producao_lote',
                        'origem_id'       => $lote->id,
                        'idempotency_key' => \Illuminate\Support\Str::uuid(),
                        'movimentado_em'  => now(),
                        'created_at'      => now(),
                    ]);

                    // 3. Atualiza tabela estoques
                    $estoqueMp = DB::table('estoques')->where('empresa_id', $this->empresa_id)->where('produto_id', $lote->produto_origem_id)->first();
                    if ($estoqueMp) {
                        DB::table('estoques')->where('id', $estoqueMp->id)->decrement('quantidade', $pesoPerda);
                    }
                }

                $lote->update([
                    'peso_produzido'   => $pesoProduzido,
                    'peso_perda'       => $pesoPerda,
                    'status'           => 'transformado',
                    'nfe_perda_status' => $pesoPerda > 0 ? 'pendente' : 'nao_aplicavel',
                    'concluido_em'     => now(),
                ]);

                return response()->json(['success' => 'Lote transformado e encerrado com sucesso! Perda apurada: ' . number_format($pesoPerda, 2, ',', '.') . ' KG']);
            });
        } catch (\Throwable $e) {
            Log::error('Erro ao concluir lote de produção: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao concluir lote: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // 📄 EMISSÃO DA NF-E DE PERDA / QUEBRA (CFOP 5927)
    // =========================================================================
    public function emitirNfePerda($loteId)
    {
        try {
            return DB::transaction(function () use ($loteId) {
                $lote = ProducaoLote::where('empresa_id', $this->empresa_id)->findOrFail($loteId);

                if ($lote->peso_perda <= 0) {
                    return response()->json(['error' => 'Este lote não possui peso de perda para emissão de nota.'], 422);
                }

                if ($lote->nfe_perda_status === 'emitida') {
                    return response()->json(['error' => 'A NF-e de perda já foi emitida para este lote.'], 422);
                }

                $configNota = ConfigNota::where('empresa_id', $this->empresa_id)->first();
                $cnpjEmpresa = preg_replace('/[^0-9]/', '', $configNota->cnpj ?? '');

                // 1. Cadastra ou localiza a própria empresa como cliente (destinatário da baixa interna)
                $clienteEmpresa = \App\Models\Cliente::where('empresa_id', $this->empresa_id)
                    ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', '') = ?", [$cnpjEmpresa])
                    ->first();

                if (!$clienteEmpresa) {
                    $clienteEmpresa = \App\Models\Cliente::create([
                        'empresa_id'       => $this->empresa_id,
                        'razao_social'     => $configNota->razao_social ?? 'EMPR. PROPRIA (BAIXA DE PERDA)',
                        'nome_fantasia'    => $configNota->nome_fantasia ?? 'BAIXA PERDA',
                        'cpf_cnpj'         => $cnpjEmpresa,
                        'ie_rg'            => $configNota->ie ?? 'ISENTO',
                        'rua'              => $configNota->logradouro ?? 'Rua Principal',
                        'numero'           => $configNota->numero ?? '1',
                        'bairro'           => $configNota->bairro ?? 'Centro',
                        'cep'              => preg_replace('/[^0-9]/', '', $configNota->cep ?? '40000000'),
                        'cidade_id'        => $configNota->codMun ?? 1,
                        'consumidor_final' => 1,
                        'contribuinte'     => 1,
                    ]);
                }

                // 2. Cria a Venda interna destinada à emissão da NF-e 5927
                $venda = Venda::create([
                    'cliente_id'    => $clienteEmpresa->id,
                    'usuario_id'    => $this->usuario_id ?? session('user_logged')['id'],
                    'valor_total'   => 0,
                    'desconto'      => 0,
                    'acrescimo'     => 0,
                    'estado'        => 'DISPONIVEL',
                    'empresa_id'    => $this->empresa_id,
                    'observacao'    => "Baixa por quebra/perda industrial - Lote: {$lote->codigo_lote}",
                    'natureza_id'   => 1, // Natureza de operação padrão para baixa (CFOP 5927)
                ]);

                $produtoId = $lote->produto_origem_id;
                $pObj = Produto::find($produtoId);

                ItemVenda::create([
                    'venda_id'     => $venda->id,
                    'produto_id'   => $pObj ? $pObj->id : 1,
                    'produto_nome' => $pObj ? $pObj->nome : 'Resíduo de Perda Industrial',
                    'quantidade'   => (float) $lote->peso_perda,
                    'valor'        => 0.00,
                    'valor_custo'  => 0.00,
                ]);

                // 3. Executa a transmissão usando o seu NFService já existente
                $configService = \App\Services\VendaService::getConfig($this->empresa_id); // ou a forma como seu ERP injeta o config
                // Caso prefira carregar o array de configuração manualmente:
                $configArray = [
                    'cnpj' => $configNota->cnpj,
                    'razao_social' => $configNota->razao_social,
                    'siglaUF' => $configNota->UF,
                    'schemes' => 'PL_009_V4',
                    'versao' => '4.00',
                    'ambiente' => (int)$configNota->ambiente,
                    'cUF' => (int)$configNota->cUF,
                    'codMun' => $configNota->codMun,
                    'UF' => $configNota->UF
                ];

                $nFService = new \App\Services\NFService($configArray, $this->empresa_id);
                $resultadoGeracao = $nFService->gerarNFe($venda->id);

                if (isset($resultadoGeracao['erros_xml'])) {
                    throw new \Exception('Erro ao gerar XML: ' . implode(' | ', $resultadoGeracao['erros_xml']));
                }

                $xmlAssinado = $nFService->sign($resultadoGeracao['xml']);
                $transmissao = $nFService->transmitir($xmlAssinado, $resultadoGeracao['chave'], $venda->id);

                // 4. Atualiza o status do lote
                $lote->update([
                    'nfe_perda_id'     => $venda->id,
                    'nfe_perda_status' => 'emitida',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'NF-e de Perda (CFOP 5927) gerada e transmitida com sucesso!'
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Erro ao emitir NF-e de perda 5927: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao emitir NF-e de perda: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // 👷‍♂️ BALANÇA CEGA DE PRODUÇÃO (CHÃO DE FÁBRICA)
    // =========================================================================
    public function balancaIndex()
    {
        // Busca todos os lotes que ainda não foram transformados/encerrados
        $lotesAbertos = ProducaoLote::where('empresa_id', $this->empresa_id)
            ->where(function($q) {
                $q->whereNotIn('status', ['transformado', 'concluido', 'encerrado'])
                    ->orWhereNull('status');
            })
            ->orderByDesc('id')
            ->get(['id', 'codigo_lote', 'created_at', 'status']);

        $balancas = BalancaConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->where('integrador', 'adp')
            ->get();

        $produtosQuery = Produto::where('empresa_id', $this->empresa_id);
        if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'inativo')) {
            $produtosQuery->where('inativo', 0);
        }
        $produtosGerados = $produtosQuery->orderBy('nome')->get();

        $funcionarios = \App\Models\Funcionario::where('empresa_id', $this->empresa_id)
            ->orderBy('nome')
            ->get();

        $usuarioLogado = \App\Models\Usuario::find($this->usuario_id ?? (session('user_logged')['id'] ?? null));
        $configNota = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $balancaPadraoUsuarioId = $usuarioLogado->balanca_padrao_id ?? null;

        $title = 'Balança Cega de Produção & Corte';

        return view('producao.balanca', compact(
            'lotesAbertos',
            'balancas',
            'produtosGerados',
            'funcionarios',
            'configNota',
            'balancaPadraoUsuarioId',
            'title'
        ));
    }

    // Salva o corte pesado na balança cega
    public function salvarCorte(Request $request)
    {
        $request->validate([
            'producao_lote_id'  => 'required|exists:producao_lotes,id',
            'produto_gerado_id' => 'required|exists:produtos,id',
            'peso_bruto'        => 'required|numeric|min:0.01',
            'equipe_ids'        => 'required|array|min:1',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $lote = ProducaoLote::where('empresa_id', $this->empresa_id)->findOrFail($request->producao_lote_id);

                if ($lote->status === 'transformado') {
                    return response()->json(['error' => 'Este lote já foi encerrado pelo gerente.'], 422);
                }

                if ($lote->status === 'novo') {
                    $lote->update(['status' => 'em_corte']);
                }

                $pesoBruto = (float) $request->peso_bruto;
                $pesoBag   = (float) $request->input('peso_bag', 0);
                $pesoLiq   = max(0, $pesoBruto - $pesoBag);

                if ($pesoLiq <= 0) {
                    return response()->json(['error' => 'O peso líquido não pode ser zero ou negativo após descontar o Peso dos Recipientes (Pallet/Bag).'], 422);
                }

                $totalCortes = ProducaoCorte::where('producao_lote_id', $lote->id)->count() + 1;
                $codigoSublote = $lote->codigo_lote . '-C' . str_pad($totalCortes, 2, '0', STR_PAD_LEFT);
                $nomesFuncionarios = \App\Models\Funcionario::whereIn('id', $request->equipe_ids)->pluck('nome')->toArray();

                $dadosCorte = [
                    'empresa_id'         => $this->empresa_id,
                    'filial_id'          => $lote->filial_id,
                    'usuario_id'         => $this->usuario_id ?? session('user_logged')['id'],
                    'producao_lote_id'   => $lote->id,
                    'codigo_sublote'     => $codigoSublote,
                    'produto_gerado_id'  => $request->produto_gerado_id,
                    'balanca_config_id'  => $request->balanca_config_id ?: null,
                    'peso_bruto'         => $pesoBruto,
                    'peso_bag'           => $pesoBag,
                    'peso_liquido'       => $pesoLiq,
                    'peso_origem'        => $request->input('peso_origem', 'balanca_cega'),
                    'quantidade_vendida' => 0,
                    'saldo_disponivel'   => $pesoLiq,
                    'status_estoque'     => 'disponivel',
                    'equipe_ids'         => $request->equipe_ids,
                    'equipe_nomes'       => implode(', ', $nomesFuncionarios),
                    'observacoes'        => $request->observacoes ?? '',
                ];

                if (\Illuminate\Support\Facades\Schema::hasColumn('producao_cortes', 'balanca_evidence_json')) {
                    $dadosCorte['balanca_evidence_json'] = $request->input('balanca_evidence_json');
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn('producao_cortes', 'camera_snapshots_json')) {
                    $dadosCorte['camera_snapshots_json'] = $request->input('camera_snapshots_json');
                }

                $corte = ProducaoCorte::create($dadosCorte);

                // Insere no estoque físico geral do ERP
                try {
                    $pProd = Produto::find($request->produto_gerado_id);
                    $valorCusto = $pProd ? (float)$pProd->valor_compra : 0;

                    DB::table('estoque_fisico_movimentos')->insert([
                        'empresa_id'     => $this->empresa_id,
                        'filial_id'      => $lote->filial_id,
                        'usuario_id'     => $corte->usuario_id,
                        'produto_id'     => $request->produto_gerado_id,
                        'tipo'           => 'entrada',
                        'peso_bruto'     => $pesoLiq,
                        'peso_impureza'  => 0,
                        'quantidade'     => $pesoLiq,
                        'valor_unitario' => $valorCusto,
                        'valor_total'    => ($pesoLiq * $valorCusto),
                        'data_movimento' => date('Y-m-d'),
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                } catch (\Throwable $ex) {
                    Log::warning('Erro ao alimentar estoque físico com sub-lote de produção: ' . $ex->getMessage());
                }

                return response()->json([
                    'success'        => true,
                    'message'        => 'Corte registrado com sucesso! Sub-lote gerado: ' . $codigoSublote,
                    'codigo_sublote' => $codigoSublote,
                    'peso_liquido'   => number_format($pesoLiq, 2, ',', '.') . ' KG'
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Erro ao salvar corte na balança cega: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao salvar corte: ' . $e->getMessage()], 500);
        }
    }

    // Salva uma pesagem parcial na balança cega
    public function salvarItemBalanca(Request $request)
    {
        // 1. Validação dos campos obrigatórios
        $request->validate([
            'producao_lote_id' => 'required|integer',
            'produto_id'       => 'required|integer',
            'peso'             => 'required|numeric|min:0.01',
            'peso_bag'         => 'nullable|numeric|min:0',
        ]);

        try {
            $lote = \App\Models\ProducaoLote::where('empresa_id', $this->empresa_id)
                ->findOrFail($request->producao_lote_id);

            $pesoBruto = (float) $request->peso;
            $pesoBag = (float) ($request->peso_bag ?? 0);
            $pesoLiquido = max(0, $pesoBruto - $pesoBag);

            // 2. Validação de Balanço de Massa (Bloqueio se ultrapassar a entrada)
            $jaProduzido = \App\Models\ProducaoLoteItem::where('producao_lote_id', $lote->id)
                ->sum('peso_liquido');
            $totalComNovo = $jaProduzido + $pesoLiquido;
            $pesoEntrada = (float) $lote->peso_entrada;

            if ($pesoEntrada > 0 && $totalComNovo > $pesoEntrada) {
                $excesso = $totalComNovo - $pesoEntrada;
                return response()->json([
                    'success' => false,
                    'error' => "Peso inválido! Esta pesagem ultrapassa o total de entrada do lote em " . number_format($excesso, 2, ',', '.') . " kg. Entrada: " . number_format($pesoEntrada, 2, ',', '.') . " kg | Já pesado: " . number_format($jaProduzido, 2, ',', '.') . " kg."
                ], 422);
            }

            // 3. Criação do item de pesagem
            $item = \App\Models\ProducaoLoteItem::create([
                'empresa_id'        => $this->empresa_id,
                'producao_lote_id'  => $request->producao_lote_id,
                'produto_id'        => $request->produto_id,
                'funcionario_id'    => $request->funcionario_id,
                'peso'              => $pesoBruto,
                'peso_bag'          => $pesoBag,
                'peso_liquido'      => $pesoLiquido,
                'balanca_config_id' => $request->balanca_config_id,
                'processado'        => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pesagem registrada com sucesso!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao salvar item na balança cega: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Erro ao registrar pesagem: ' . $e->getMessage()], 500);
        }
    }

    // Retorna todos os itens AINDA NÃO PROCESSADOS de um lote para o grid
    public function listarItensBalanca($loteId)
    {
        $itens = \App\Models\ProducaoLoteItem::with(['produto', 'funcionario'])
            ->where('empresa_id', $this->empresa_id)
            ->where('producao_lote_id', $loteId)
            ->where('processado', 0) // <-- Puxa só as pesagens novas da sessão atual
            ->orderBy('id', 'desc')
            ->get();

        $totalLiquido = $itens->sum('peso_liquido');

        return response()->json([
            'success'       => true,
            'itens'         => $itens,
            'total_liquido' => $totalLiquido
        ]);
    }

    // Permite excluir uma pesagem parcial (caso o operador tenha errado)
    public function deletarItemBalanca($id)
    {
        try {
            $item = \App\Models\ProducaoLoteItem::where('empresa_id', $this->empresa_id)->findOrFail($id);
            $item->delete();
            return response()->json(['success' => true, 'message' => 'Pesagem removida.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao remover pesagem.'], 500);
        }
    }


    // Finaliza o lote na balança, carimba o número do ticket e dispara WhatsApp em segundo plano
    public function finalizarLoteBalanca($id)
    {
        try {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
                $lote = \App\Models\ProducaoLote::where('empresa_id', $this->empresa_id)->findOrFail($id);

                // 1. Busca APENAS os itens da sessão atual (não processados)
                $itensPendentes = \App\Models\ProducaoLoteItem::where('producao_lote_id', $lote->id)
                    ->where('processado', 0)
                    ->get();

                if ($itensPendentes->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'Não há pesagens pendentes registradas no grid para gerar este ticket.'
                    ], 422);
                }

                $totalLiquido = $itensPendentes->sum('peso_liquido');

                // 2. Calcula o número sequencial do ticket para este lote
                $ultimoTicket = \App\Models\ProducaoLoteItem::where('producao_lote_id', $lote->id)
                    ->max('ticket_numero') ?? 0;
                $proximoTicket = $ultimoTicket + 1;

                // 3. Processa cada produto pesado neste ticket
                $qtdCortesExistentes = \DB::table('producao_cortes')
                    ->where('producao_lote_id', $lote->id)
                    ->count();

                foreach ($itensAgrupados as $produtoId => $itensDoProduto) {
                    $pesoBrutoTotal   = (float) $itensDoProduto->sum('peso');
                    $pesoBagTotal     = (float) $itensDoProduto->sum('peso_bag');
                    $pesoTotalProduto = (float) $itensDoProduto->sum('peso_liquido');

                    // Geração do código sequencial do sublote (ex: LOTE-202610-0001-C01)
                    $qtdCortesExistentes++;
                    $sequencialSublote = str_pad($qtdCortesExistentes, 2, '0', STR_PAD_LEFT);
                    $codigoSublote = "{$lote->codigo_lote}-C{$sequencialSublote}";

                    // ---------------------------------------------------------
                    // A. GRAVAÇÃO NA TABELA PRODUCAO_CORTES (Fardos / Sublotes)
                    // ---------------------------------------------------------
                    \DB::table('producao_cortes')->insert([
                        'empresa_id'        => $this->empresa_id,
                        'filial_id'         => $this->filial_id ?? null,
                        'usuario_id'        => $this->usuario_id ?? auth()->id(),
                        'producao_lote_id'  => $lote->id,
                        'codigo_sublote'    => $codigoSublote,
                        'produto_gerado_id' => $produtoId,
                        'balanca_config_id' => $itensDoProduto->first()->balanca_config_id ?? null,
                        'peso_bruto'        => $pesoBrutoTotal,
                        'peso_bag'          => $pesoBagTotal,
                        'peso_liquido'      => $pesoTotalProduto,
                        'peso_origem'       => 'balanca_cega',
                        'quantidade_vendida'=> 0.0000,
                        'saldo_disponivel'  => $pesoTotalProduto,
                        'status_estoque'    => 'disponivel',
                        'equipe_nomes'      => $itensDoProduto->first()->equipe_nomes ?? null,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);

                    // ---------------------------------------------------------
                    // B. APONTAMENTO DE PRODUÇÃO
                    // ---------------------------------------------------------
                    \DB::table('apontamentos')->insert([
                        'empresa_id'        => $this->empresa_id,
                        'producao_lote_id'  => $lote->id,
                        'produto_id'        => $produtoId,
                        'quantidade'        => $pesoTotalProduto,
                        'data'              => now()->format('Y-m-d'),
                        'data_registro'     => now(),
                        'data_apontamento'  => now(),
                        'usuario_id'        => $this->usuario_id ?? auth()->id(),
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);

                    // ---------------------------------------------------------
                    // C. ALTERAÇÃO DE ESTOQUE
                    // ---------------------------------------------------------
                    \DB::table('alteracao_estoques')->insert([
                        'empresa_id' => $this->empresa_id,
                        'produto_id' => $produtoId,
                        'quantidade' => $pesoTotalProduto,
                        'tipo'       => 'incremento',
                        'observacao' => 'Produção Balança - Lote: ' . $lote->codigo_lote . ' (' . $codigoSublote . ')',
                        'usuario_id' => $this->usuario_id ?? auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // ---------------------------------------------------------
                    // D. STOCK MOVEMENTS (Auditoria UUID)
                    // ---------------------------------------------------------
                    \DB::table('stock_movements')->insert([
                        'empresa_id'      => $this->empresa_id,
                        'produto_id'      => $produtoId,
                        'quantidade'      => $pesoTotalProduto,
                        'tipo'            => 'entrada',
                        'contexto'        => 'producao',
                        'origem_tipo'     => 'producao_lote',
                        'origem_id'       => $lote->id,
                        'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
                        'movimentado_em'  => now(),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    // ---------------------------------------------------------
                    // E. ESTOQUE FÍSICO MOVIMENTOS
                    // ---------------------------------------------------------
                    if (\Illuminate\Support\Facades\Schema::hasTable('estoque_fisico_movimentos')) {
                        \DB::table('estoque_fisico_movimentos')->insert([
                            'empresa_id'     => $this->empresa_id,
                            'filial_id'      => $this->filial_id ?? null,
                            'usuario_id'     => $this->usuario_id ?? auth()->id(),
                            'produto_id'     => $produtoId,
                            'pesagem_id'     => null,
                            'peso_bruto'     => $pesoBrutoTotal,
                            'peso_impureza'  => $pesoBagTotal,
                            'tipo'           => 'entrada',
                            'quantidade'     => $pesoTotalProduto,
                            'valor_unitario' => 0.0000,
                            'valor_total'    => 0.00,
                            'data_movimento' => now()->format('Y-m-d'),
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]);
                    }

                    // ---------------------------------------------------------
                    // F. TABELA ESTOQUES (Saldo Atual)
                    // ---------------------------------------------------------
                    $estoqueAcabado = \DB::table('estoques')
                        ->where('empresa_id', $this->empresa_id)
                        ->where('produto_id', $produtoId)
                        ->first();

                    if ($estoqueAcabado) {
                        \DB::table('estoques')
                            ->where('id', $estoqueAcabado->id)
                            ->update([
                                'quantidade' => \DB::raw("quantidade + {$pesoTotalProduto}"),
                                'updated_at' => now(),
                            ]);
                    } else {
                        \DB::table('estoques')->insert([
                            'empresa_id'   => $this->empresa_id,
                            'produto_id'   => $produtoId,
                            'quantidade'   => $pesoTotalProduto,
                            'valor_compra' => 0.00,
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ]);
                    }
                }

                // -------------------------------------------------------------
                // 4. BAIXA DA MATÉRIA-PRIMA ORIGINAL (Origem do Lote)
                // -------------------------------------------------------------
                $produtoOrigemId = $lote->produto_id ?? $lote->produto_origem_id;
                if ($produtoOrigemId) {
                    \DB::table('alteracao_estoques')->insert([
                        'empresa_id' => $this->empresa_id,
                        'produto_id' => $produtoOrigemId,
                        'quantidade' => $totalLiquido,
                        'tipo'       => 'reducao',
                        'observacao' => 'Baixa Matéria-Prima Corte - Lote: ' . $lote->codigo_lote . ' (Ticket #' . $proximoTicket . ')',
                        'usuario_id' => $this->usuario_id ?? auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    \DB::table('stock_movements')->insert([
                        'empresa_id'      => $this->empresa_id,
                        'produto_id'      => $produtoOrigemId,
                        'quantidade'      => $totalLiquido,
                        'tipo'            => 'saida',
                        'contexto'        => 'producao',
                        'origem_tipo'     => 'producao_lote',
                        'origem_id'       => $lote->id,
                        'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
                        'movimentado_em'  => now(),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    if (\Illuminate\Support\Facades\Schema::hasTable('estoque_fisico_movimentos')) {
                        \DB::table('estoque_fisico_movimentos')->insert([
                            'empresa_id'     => $this->empresa_id,
                            'filial_id'      => $this->filial_id ?? null,
                            'usuario_id'     => $this->usuario_id ?? auth()->id(),
                            'produto_id'     => $produtoOrigemId,
                            'pesagem_id'     => null,
                            'peso_bruto'     => $totalLiquido,
                            'peso_impureza'  => 0.00,
                            'tipo'           => 'saida',
                            'quantidade'     => $totalLiquido,
                            'valor_unitario' => 0.0000,
                            'valor_total'    => 0.00,
                            'data_movimento' => now()->format('Y-m-d'),
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]);
                    }

                    $estoqueMp = \DB::table('estoques')
                        ->where('empresa_id', $this->empresa_id)
                        ->where('produto_id', $produtoOrigemId)
                        ->first();

                    if ($estoqueMp) {
                        \DB::table('estoques')
                            ->where('id', $estoqueMp->id)
                            ->update([
                                'quantidade' => \DB::raw("GREATEST(0, quantidade - {$totalLiquido})"),
                                'updated_at' => now(),
                            ]);
                    }
                }

                // 5. Carimba o número do ticket e marca como processado
                $idsProcessados = $itensPendentes->pluck('id')->toArray();
                \App\Models\ProducaoLoteItem::whereIn('id', $idsProcessados)->update([
                    'processado'    => 1,
                    'ticket_numero' => $proximoTicket
                ]);

                // -------------------------------------------------------------
                // 📱 DISPARO VIA WHATSAPP AUTOMÁTICO (WhatsAppUtil)
                // -------------------------------------------------------------
                try {
                    $empresaId = $this->empresa_id ?? session('user_logged')['empresa'] ?? $lote->empresa_id ?? 1;
                    $configNota = \App\Models\ConfigNota::where('empresa_id', $empresaId)->first();

                    if ($configNota && class_exists('\App\Utils\WhatsAppUtil')) {
                        $destinatarios = [];

                        // 1. Tratamento seguro do array / JSON
                        if (!empty($configNota->pesagem_whatsapp_destinos_json)) {
                            $decoded = is_array($configNota->pesagem_whatsapp_destinos_json)
                                ? $configNota->pesagem_whatsapp_destinos_json
                                : json_decode($configNota->pesagem_whatsapp_destinos_json, true);

                            if (is_array($decoded)) {
                                foreach ($decoded as $item) {
                                    if (is_string($item) || is_numeric($item)) {
                                        $destinatarios[] = (string)$item;
                                    } elseif (is_array($item)) {
                                        $destinatarios[] = (string)($item['numero'] ?? $item['telefone'] ?? $item['whatsapp'] ?? reset($item));
                                    }
                                }
                            }
                        }

                        // 2. Destino avulso (se preenchido)
                        if (!empty($configNota->pesagem_whatsapp_destino)) {
                            $destinatarios[] = (string)$configNota->pesagem_whatsapp_destino;
                        }

                        // Normalização dos números (DDI 55)
                        $numerosValidos = [];
                        foreach ($destinatarios as $dest) {
                            $limpo = preg_replace('/[^0-9]/', '', (string)$dest);
                            if (strlen($limpo) >= 10) {
                                if (substr($limpo, 0, 2) !== '55') {
                                    $limpo = '55' . $limpo;
                                }
                                $numerosValidos[$limpo] = $limpo;
                            }
                        }

                        if (!empty($numerosValidos)) {
                            $mensagem = "🏭 *TICKET DE PRODUÇÃO GERADO #{$proximoTicket}*\n"
                                . "• Lote: {$lote->codigo_lote}\n"
                                . "• Total Líquido: " . number_format($totalLiquido, 2, ',', '.') . " kg\n"
                                . "• Data/Hora: " . date('d/m/Y H:i') . "\n"
                                . "• *Itens Pesados:*\n";

                            foreach ($itensAgrupados as $pId => $itensProd) {
                                $nomeProd = optional($itensProd->first()->produto)->nome ?? 'Item';
                                $subTotalProd = $itensProd->sum('peso_liquido');
                                $mensagem .= "  - {$nomeProd}: " . number_format($subTotalProd, 2, ',', '.') . " kg\n";
                            }
                            $mensagem .= "_FerSoft ERP - Gestão Industrial_";

                            $whatsAppUtil = app(\App\Utils\WhatsAppUtil::class);

                            foreach ($numerosValidos as $numEnvio) {
                                \Log::info("WhatsApp Produção: Enviando ticket para {$numEnvio}...");
                                if (method_exists($whatsAppUtil, 'sendMessage')) {
                                    $whatsAppUtil->sendMessage($numEnvio, $mensagem, $empresaId);
                                } elseif (method_exists($whatsAppUtil, 'send')) {
                                    $whatsAppUtil->send($numEnvio, $mensagem);
                                }
                            }
                        } else {
                            \Log::warning("WhatsApp Produção: Nenhum número válido encontrado para envio.");
                        }
                    }
                } catch (\Throwable $ex) {
                    \Log::error('Erro ao disparar WhatsApp de produção: ' . $ex->getMessage());
                }

                return response()->json([
                    'success'       => true,
                    'message'       => 'Pesagens registradas e enviadas ao WhatsApp!',
                    'codigo_lote'   => $lote->codigo_lote,
                    'ticket_numero' => $proximoTicket,
                    'total_liquido' => number_format($totalLiquido, 2, ',', '.')
                ]);
            });
        } catch (\Throwable $e) {
            \Log::error('Erro ao finalizar ticket do lote: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Erro ao processar: ' . $e->getMessage()], 500);
        }
    }

    // Carrega dados para impressão filtrando pelo número do ticket
    public function imprimirTicketLote(\Illuminate\Http\Request $request, $id)
    {
        $lote = \App\Models\ProducaoLote::where('empresa_id', $this->empresa_id)
            ->with(['fornecedor'])
            ->findOrFail($id);

        $numeroTicket = $request->input('ticket');

        if (!$numeroTicket) {
            $numeroTicket = \App\Models\ProducaoLoteItem::where('producao_lote_id', $lote->id)
                ->max('ticket_numero');
        }

        $itens = \App\Models\ProducaoLoteItem::with(['produto', 'funcionario'])
            ->where('producao_lote_id', $lote->id)
            ->when($numeroTicket, function($q) use ($numeroTicket) {
                $q->where('ticket_numero', $numeroTicket);
            })
            ->get();

        $configNota = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();

        $totalPeso = $itens->sum('peso');
        $totalTara = $itens->sum('peso_bag');
        $totalLiquido = $itens->sum('peso_liquido');

        return view('producao.ticket_lote', compact('lote', 'configNota', 'itens', 'totalPeso', 'totalTara', 'totalLiquido', 'numeroTicket'));
    }

    // Reabre um lote fechado anteriormente (Exige senha 1982)
    public function reabrirLote(Request $request, $id)
    {
        try {
            $senha = $request->input('senha');
            if ($senha !== '1982') {
                return response()->json(['error' => 'Senha de gestor incorreta! Acesso negado.'], 403);
            }

            return DB::transaction(function () use ($id) {
                $lote = ProducaoLote::where('empresa_id', $this->empresa_id)->findOrFail($id);

                if ($lote->status !== 'transformado' && $lote->status !== 'concluido') {
                    return response()->json(['error' => 'Este lote não está encerrado.'], 422);
                }

                // Estorna a perda apurada anteriormente devolvendo o peso para a matéria-prima
                $pesoPerda = (float) $lote->peso_perda;
                if ($pesoPerda > 0 && $lote->produto_origem_id) {

                    // 1. Reverte o estoque na tabela alteracao_estoques
                    DB::table('alteracao_estoques')->insert([
                        'empresa_id' => $this->empresa_id,
                        'produto_id' => $lote->produto_origem_id,
                        'quantidade' => $pesoPerda,
                        'tipo'       => 'incremento',
                        'observacao' => 'Estorno por Reabertura de Lote: ' . $lote->codigo_lote,
                        'usuario_id' => $this->usuario_id ?? auth()->id(),
                        'created_at' => now(),
                    ]);

                    // 2. Registra na auditoria stock_movements
                    DB::table('stock_movements')->insert([
                        'empresa_id'      => $this->empresa_id,
                        'produto_id'      => $lote->produto_origem_id,
                        'quantidade'      => $pesoPerda,
                        'tipo'            => 'entrada',
                        'contexto'        => 'producao_estorno',
                        'origem_tipo'     => 'producao_lote',
                        'origem_id'       => $lote->id,
                        'idempotency_key' => \Illuminate\Support\Str::uuid(),
                        'movimentado_em'  => now(),
                        'created_at'      => now(),
                    ]);

                    // 3. Devolve a quantidade para o estoque físico
                    $estoqueMp = DB::table('estoques')
                        ->where('empresa_id', $this->empresa_id)
                        ->where('produto_id', $lote->produto_origem_id)
                        ->first();

                    if ($estoqueMp) {
                        DB::table('estoques')->where('id', $estoqueMp->id)->increment('quantidade', $pesoPerda);
                    }
                }

                // Retorna o lote para o status de em_corte e limpa os dados de encerramento
                $lote->update([
                    'status'           => 'em_corte',
                    'peso_perda'       => 0,
                    'nfe_perda_status' => 'pendente',
                    'concluido_em'     => null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Lote reaberto com sucesso! O estoque de perda foi estornado e o lote retornou para o fluxo de produção.'
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Erro ao reabrir lote de produção: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao reabrir lote: ' . $e->getMessage()], 500);
        }
    }


    // Exibe o Dossiê e Histórico Completo do Lote
    /**
     * Dossiê de Rastreabilidade e Histórico do Lote
     */
    public function dossieLote($id)
    {
        return $this->dossie($id);
    }

    public function dossie($id)
    {
        $lote = \App\Models\ProducaoLote::where('empresa_id', $this->empresa_id)
            ->with(['fornecedor', 'itens.produto', 'itens.funcionario', 'itens.usuario', 'produtoOrigem'])
            ->findOrFail($id);

        $pesoEntrada = (float) $lote->peso_entrada;
        $totalProduzido = (float) $lote->itens->sum('peso_liquido');
        $perdaCalculada = max(0, $pesoEntrada - $totalProduzido);
        $percentualPerda = $pesoEntrada > 0 ? round(($perdaCalculada / $pesoEntrada) * 100, 1) : 0;

        // 1. Totalizadores por Produto Gerado
        $resumoProdutos = $lote->itens->groupBy('produto_id')->map(function ($itens) {
            $primeiro = $itens->first();
            return [
                'produto_nome'        => optional($primeiro->produto)->nome ?? 'Produto ID ' . $primeiro->produto_id,
                'peso_total'          => (float) $itens->sum('peso_liquido'),
                'quantidade_pesagens' => $itens->count(),
            ];
        })->values();

        // 2. Busca todas as saídas (vendas) vinculadas a este lote
        $saidasLote = \DB::table('tickets_pesagem as tp')
            ->join('pesagens as p', 'p.id', '=', 'tp.pesagem_id')
            ->leftJoin('clientes as c', 'c.id', '=', 'p.cliente_id')
            ->leftJoin('veiculos as v', 'v.id', '=', 'p.veiculo_id')
            ->leftJoin('produtos as pr', 'pr.id', '=', 'tp.produto_id')
            ->where('tp.producao_lote_id', $lote->id)
            ->where('p.tipo', 'venda')
            ->where('p.status', '!=', 'cancelado')
            ->select(
                'tp.id as ticket_id',
                'p.id as pesagem_id',
                'p.venda_id',
                'p.created_at as data_saida',
                'c.razao_social as cliente_nome',
                'v.placa as veiculo_placa',
                'pr.nome as produto_nome',
                \DB::raw('GREATEST(0, tp.peso - COALESCE(tp.peso_bag, 0)) as peso_saida')
            )
            ->orderBy('p.created_at', 'desc')
            ->get();

        $totalExpedido = (float) $saidasLote->sum('peso_saida');
        $saldoRestanteGalpao = max(0, $totalProduzido - $totalExpedido);

        // 3. Cálculo da Margem Financeira (Blindado contra negativo irreal)
        $custoEntrada = (float) ($lote->valor_total_custo ?? $lote->valor_total ?? 0);
        if ($custoEntrada <= 0 && $lote->produto_id) {
            $prodOrigem = \App\Models\Produto::find($lote->produto_id);
            $custoEntrada = $pesoEntrada * (float) ($prodOrigem->valor_compra ?? 0);
        }

        $valorEstimadoProduzido = 0;
        foreach ($lote->itens as $item) {
            $precoUnitario = (float) (optional($item->produto)->valor_venda ?? 0);
            $valorEstimadoProduzido += ($item->peso_liquido * $precoUnitario);
        }

        if ($valorEstimadoProduzido > 0 && $custoEntrada > 0) {
            $lucroBruto = $valorEstimadoProduzido - $custoEntrada;
            $margemLucro = round(($lucroBruto / $custoEntrada) * 100, 1);
        } else {
            $lucroBruto = 0.00;
            $margemLucro = 0.0;
        }

        return view('producao.dossie', compact(
            'lote',
            'pesoEntrada',
            'totalProduzido',
            'perdaCalculada',
            'percentualPerda',
            'lucroBruto',
            'margemLucro',
            'resumoProdutos',
            'saidasLote',
            'totalExpedido',
            'saldoRestanteGalpao'
        ));
    }
}
