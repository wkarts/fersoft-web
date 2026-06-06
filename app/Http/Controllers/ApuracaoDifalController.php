<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApuracaoDifal;
use App\Models\ApuracaoDifalItem;
use App\Models\ContaPagar;
use App\Models\Fornecedor;
use App\Models\CategoriaConta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ApuracaoDifalController extends BaseController
{
    // 1. Declaração das propriedades que o BaseController analisa
    protected $formTitle;
    protected $Prefix_Route;
    protected $listView;
    protected $registerView;
    protected $redirectPage;

    // 2. Construtor perfeitamente alinhado com a classe pai
    public function __construct(ApuracaoDifal $model)
    {
        parent::__construct(); // 🔥 ATIVA O BASECONTROLLER COM SEGURANÇA
        
        $this->model = $model;
        $this->formTitle = 'Apuração de ICMS Difal (Uso e Consumo)';
        $this->Prefix_Route = 'compraFiscal.difal'; 
        $this->listView = 'compraFiscal.apuracao_difal.index';
        $this->registerView = 'compraFiscal.apuracao_difal.create';
        $this->redirectPage = 'compras/apuracao-difal';
    }

    public function rules(): array { return []; }
    public function messages(): array { return []; }

    public function index()
    {
        $data = $this->model->where('empresa_id', $this->empresa_id)->orderBy('id', 'desc')->get();
        return view($this->listView, compact('data'))->with([
            'formTitle' => $this->formTitle,
            'title'     => $this->formTitle,
            'Prefix_Route' => $this->Prefix_Route
        ]);
    }

    public function create()
    {
        $fornecedores = Fornecedor::where('empresa_id', $this->empresa_id)->orderBy('razao_social', 'asc')->get();
        $categorias = CategoriaConta::where('empresa_id', $this->empresa_id)->orderBy('nome', 'asc')->get();

        return view($this->registerView)->with([
            'formTitle'    => $this->formTitle,
            'title'        => 'Nova Apuração',
            'fornecedores' => $fornecedores,
            'categorias'   => $categorias,
            'Prefix_Route' => $this->Prefix_Route
        ]);
    }

    public function processar(Request $request)
    {
        $data_inicial = $request->data_inicial;
        $data_final   = $request->data_final;

        // 1. Consulta SQL trazendo a chave para abrir o arquivo físico
        $notas = DB::table('item_compras') 
            ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
            ->leftJoin('fornecedors', 'fornecedors.id', '=', 'compras.fornecedor_id')
            ->leftJoin('cidades', 'cidades.id', '=', 'fornecedors.cidade_id')
            ->whereBetween('compras.data_emissao', [$data_inicial, $data_final])
            ->where('item_compras.cfop_entrada', '2556')
            ->where('item_compras.cst_icms', '090')
            ->where('compras.empresa_id', $this->empresa_id)
            ->where(function($query) {
                $query->whereRaw("UPPER(TRIM(cidades.uf)) <> 'BA'")
                      ->orWhereNull('cidades.uf');
            })
            ->select(
                'compras.id as nota_id', 
                'compras.nf as numero_nota',
                'compras.chave', // Chave pura de 44 dígitos
                'fornecedors.razao_social as fornecedor', 
                DB::raw("COALESCE(cidades.uf, 'ND') as uf_origem"),
                'item_compras.valor_unitario', 
                'item_compras.quantidade'
            )
            ->orderBy('item_compras.compra_id') // Garante o agrupamento correto para a contagem
            ->orderBy('item_compras.id')
            ->get();

        if ($notas->isEmpty()) {
            return response()->json([
                'status'  => 'vazio', 
                'message' => 'Nenhum registro interestadual legítimo foi encontrado para o período.'
            ], 200);
        }

        $itensCalculados = [];
        $totalGeralDifal = 0;
        $aliqInternaBA = 0.205; 

        // Contador para mapear a posição física exata dos itens da mesma nota (1º item, 2º item...)
        $contadorItemNota = [];

        foreach ($notas as $n) {
            if (strtoupper(trim($n->uf_origem)) === 'BA') {
                continue;
            }

            // Garante o incremento por ID numérico da nota
            $idNotaAtual = $n->nota_id;
            if (!isset($contadorItemNota[$idNotaAtual])) {
                $contadorItemNota[$idNotaAtual] = 0;
            }
            $contadorItemNota[$idNotaAtual]++;
            $posicaoFiscalItem = $contadorItemNota[$idNotaAtual];

            $valorOperacao = $n->valor_unitario * $n->quantidade;
            $aliqOrigem = 0;
            $chaveNota = trim($n->chave);

            // 🟢 LEITURA DIRETA DO ARQUIVO CHAVE.XML
            if (!empty($chaveNota)) {
                $caminhoDfe = public_path("xml_dfe/{$chaveNota}.xml");
                $caminhoEntrada = public_path("xml_entrada/{$chaveNota}.xml");
                $xmlTexto = null;

                if (file_exists($caminhoDfe)) {
                    $xmlTexto = file_get_contents($caminhoDfe);
                } elseif (file_exists($caminhoEntrada)) {
                    $xmlTexto = file_get_contents($caminhoEntrada);
                }

                if ($xmlTexto) {
                try {
                    $xmlTextoLimpo = str_replace('xmlns=', '_xmlns=', $xmlTexto);
                    $xml = new \SimpleXMLElement($xmlTextoLimpo);
                    
                    if (isset($xml->NFe->infNFe->det)) {
                        foreach ($xml->NFe->infNFe->det as $det) {
                            $numeroItemXml = (int)$det->attributes()->nItem;
                            
                            // Se a ordem física do XML bater com a ordem do banco
                            if ($numeroItemXml === $posicaoFiscalItem) {
                                if (isset($det->imposto->ICMS)) {
                                    // 🟢 EVOLUÇÃO: Varre qualquer grupo de ICMS (ICMS00, ICMS20, ICMS90, ICMSSN102, etc.)
                                    foreach ($det->imposto->ICMS->children() as $grupoIcms) {
                                        if (isset($grupoIcms->pICMS)) {
                                            $aliqOrigem = (float)$grupoIcms->pICMS / 100;
                                            break 2; // Achou a alíquota, sai dos dois laços (do ICMS e dos itens)
                                        }
                                        // Caso seja Simples Nacional e use a tag pCredSN (Crédito do Simples)
                                        if (isset($grupoIcms->pCredSN)) {
                                            $aliqOrigem = (float)$grupoIcms->pCredSN / 100;
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $aliqOrigem = 0;
                }
            }
            }

            // 🟢 REFUGE DE SEGURANÇA FISCAL (CONTINGÊNCIA):
            // Se o arquivo não existir ou for Simples Nacional total sem pICMS, usa a UF real do Danfe
            if ($aliqOrigem <= 0) {
                $ufOrigem = strtoupper(trim($n->uf_origem));
                // Estados cujas operações normais de uso/consumo destinadas à BA possuem carga interestadual de 7%
                $regiaoSulSudeste = ['SP', 'RJ', 'MG', 'PR', 'RS', 'SC'];
                
                $aliqOrigem = in_array($ufOrigem, $regiaoSulSudeste) ? 0.07 : 0.12;
            }

            // --- Fórmulas Matemáticas Oficiais da Base Dupla ---
            $baseDupla = ($valorOperacao * (1 - $aliqOrigem)) / (1 - $aliqInternaBA);
            $difal = ($baseDupla * $aliqInternaBA) - ($baseDupla * $aliqOrigem);

            $itensCalculados[] = [
                'nota_fiscal_id' => $n->nota_id, 
                'numero_nota' => $n->numero_nota,
                'emitente_nome' => $n->fornecedor ?? 'Fornecedor Não Identificado', 
                'valor_operacao' => number_format($valorOperacao, 2, ',', '.'),
                'aliquota_origem' => number_format(($aliqOrigem * 100), 1, ',', '.'),
                'aliquota_destino' => ($aliqInternaBA * 100),
                'base_calculo_dupla' => number_format($baseDupla, 2, ',', '.'), 
                'valor_difal' => number_format($difal, 2, ',', '.'),
                'base_raw' => $baseDupla, 
                'difal_raw' => $difal,
                'icms_origem' => number_format($baseDupla * $aliqOrigem, 2, ',', '.'),
                'icms_destino' => number_format($baseDupla * $aliqInternaBA, 2, ',', '.')
            ];
            $totalGeralDifal += $difal;
        }

        if (empty($itensCalculados)) {
            return response()->json([
                'status'  => 'vazio', 
                'message' => 'Nenhum registro restou após as filtragens de estado.'
            ], 200);
        }

        if ($request->confirmar_gravacao) {
            return $this->salvarApuracao($request, $itensCalculados, $totalGeralDifal);
        }

        return response()->json([
            'status' => 'success', 
            'itens' => $itensCalculados, 
            'total_difal' => number_format($totalGeralDifal, 2, ',', '.')
        ]);
    }
  
    private function salvarApuracao($request, $itens, $total)
    {
        return DB::transaction(function () use ($request, $itens, $total) {
            $apuracao = $this->model->create([
                'empresa_id' => $this->empresa_id, 'filial_id' => $this->filial_id, 'usuario_id' => Auth::id(),
                'referencia' => date('m/Y', strtotime($request->data_inicial)), 'data_inicial' => $request->data_inicial,
                'data_final' => $request->data_final, 'valor_total_difal' => $total, 'status' => 'fechada'
            ]);

            foreach ($itens as $i) {
                ApuracaoDifalItem::create([
                    'apuracao_difal_id' => $apuracao->id, 'empresa_id' => $this->empresa_id, 'filial_id' => $this->filial_id,
                    'nota_fiscal_id' => $i['nota_fiscal_id'], 'numero_nota' => $i['numero_nota'], 'emitente_nome' => $i['emitente_nome'],
                    'valor_operacao' => str_replace(['.', ','], ['', '.'], $i['valor_operacao']), 'aliquota_origem' => $i['aliquota_origem'],
                    'aliquota_destino' => $i['aliquota_destino'], 'base_calculo_dupla' => $i['base_raw'], 'valor_difal' => $i['difal_raw']
                ]);
            }

            $financeiro = ContaPagar::create([
                'empresa_id' => $this->empresa_id, 'filial_id' => $this->filial_id, 'usuario_id' => Auth::id(),
                'fornecedor_id' => $request->fornecedor_id, 'categoria_id' => $request->categoria_id,
                'valor_integral' => $total, 'data_emissao' => date('Y-m-d'), 'data_vencimento' => date('Y-m-15', strtotime('+1 month')),
                'referencia' => "DIFAL USO/CONSUMO - " . $apuracao->referencia, 'status' => false
            ]);

            $apuracao->update(['contas_a_pagar_id' => $financeiro->id]);
            return response()->json(['status' => 'success']);
        });
    }

    public function cancelar($id)
    {
        return DB::transaction(function () use ($id) {
            $apuracao = $this->model->findOrFail($id);
            if ($apuracao->contas_a_pagar_id) {
                $cp = ContaPagar::find($apuracao->contas_a_pagar_id);
                if ($cp && $cp->status == true) return response()->json(['message' => 'Título já pago!'], 422);
                if ($cp) $cp->delete();
            }
            ApuracaoDifalItem::where('apuracao_difal_id', $id)->delete();
            $apuracao->delete();
            return response()->json(['status' => 'success']);
        });
    }

    public function show($id)
    {
        $items = ApuracaoDifalItem::where('apuracao_difal_id', $id)->get();
        return view('compraFiscal.apuracao_difal.detalhes_modal', compact('items'));
    }
    
    public function imprimir($id)
    {
        $apuracao = $this->model->findOrFail($id);
        $items = ApuracaoDifalItem::where('apuracao_difal_id', $id)->get();

        return view('compraFiscal.apuracao_difal.relatorio_pdf', compact('apuracao', 'items'))->with([
            'title' => 'Impressão de Apuração DIFAL - ' . $apuracao->referencia
        ]);
    }
  // 🟢 CENTRAL DE SANEAMENTO FISCAL GLOBAL (SEM TRAVA DE DATAS PARA REGRAS DE USO/CONSUMO)
    public function corrigirCfops(Request $request)
    {
        $empresa_id = $request->empresa_id ?? request()->empresa_id;
        $data_inicial = $request->data_inicial;
        $data_final   = $request->data_final;

        return DB::transaction(function () use ($data_inicial, $data_final, $empresa_id) {
            
            // --- REGRA 1 (GLOBAL): CFOP 2102/2101 -> Uso/Consumo (2556 ou 1556) ---
            // 🟢 EVOLUÇÃO: Removido o filtro de datas para corrigir Junho, Maio ou qualquer período de uma vez só!
            $foraBA_2102 = DB::table('item_compras')
                ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
                ->join('fornecedors', 'fornecedors.id', '=', 'compras.fornecedor_id')
                ->join('cidades', 'cidades.id', '=', 'fornecedors.cidade_id')
                ->where('compras.empresa_id', $empresa_id)
                ->whereRaw("UPPER(TRIM(cidades.uf)) <> 'BA'") // ◄--- Pega realmente quem é de fora
                ->whereIn('item_compras.cfop_entrada', ['2102', '2101'])
                ->whereRaw('CAST(item_compras.cst_icms AS UNSIGNED) = 90')
                ->update([
                    'cfop_entrada' => '2556',
                    'cst_icms'     => '090'
                ]);

            $dentroBA_2102 = DB::table('item_compras')
                ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
                ->join('fornecedors', 'fornecedors.id', '=', 'compras.fornecedor_id')
                ->join('cidades', 'cidades.id', '=', 'fornecedors.cidade_id')
                ->where('compras.empresa_id', $empresa_id)
                ->whereRaw("UPPER(TRIM(cidades.uf)) = 'BA'") // ◄--- Garante o cruzamento exato para a Bahia
                ->whereIn('item_compras.cfop_entrada', ['2102', '2101'])
                ->whereRaw('CAST(item_compras.cst_icms AS UNSIGNED) = 90')
                ->update([
                    'cfop_entrada' => '1556',
                    'cst_icms'     => '090'
                ]);

            // --- REGRA 2: CFOP 1403 -> 1407 (Uso/Consumo ST Interno) ---
            $interna_1403 = DB::table('item_compras')
                ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
                ->where('compras.empresa_id', $empresa_id)
                ->where('item_compras.cfop_entrada', '1403')
                ->whereRaw('CAST(item_compras.cst_icms AS UNSIGNED) = 60')
                ->update(['item_compras.cfop_entrada' => '1407']);

            // --- REGRA 3: CFOP 2403 -> 2407 ou 1407 (Uso/Consumo ST Interestadual) ---
            $foraBA_2403 = DB::table('item_compras')
                ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
                ->join('fornecedors', 'fornecedors.id', '=', 'compras.fornecedor_id')
                ->join('cidades', 'cidades.id', '=', 'fornecedors.cidade_id')
                ->where('compras.empresa_id', $empresa_id)
                ->where('cidades.uf', '<>', 'BA')
                ->where('item_compras.cfop_entrada', '2403')
                ->whereRaw('CAST(item_compras.cst_icms AS UNSIGNED) = 60')
                ->update(['item_compras.cfop_entrada' => '2407']);

            $dentroBA_2403 = DB::table('item_compras')
                ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
                ->join('fornecedors', 'fornecedors.id', '=', 'compras.fornecedor_id')
                ->join('cidades', 'cidades.id', '=', 'fornecedors.cidade_id')
                ->where('compras.empresa_id', $empresa_id)
                ->where('cidades.uf', '=', 'BA')
                ->where('item_compras.cfop_entrada', '2403')
                ->whereRaw('CAST(item_compras.cst_icms AS UNSIGNED) = 60')
                ->update(['item_compras.cfop_entrada' => '1407']);

            // --- REGRA 4: Notas sem CFOP Informado -> Entrada Padrão (1102 CST 051) ---
            $semCfop = DB::table('item_compras')
                ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
                ->where('compras.empresa_id', $empresa_id)
                ->where(function($query) {
                    $query->whereNull('item_compras.cfop_entrada')
                          ->orWhere('item_compras.cfop_entrada', '')
                          ->orWhere('item_compras.cfop_entrada', 0);
                })
                ->update([
                    'cfop_entrada' => '1102',
                    'cst_icms'     => '051'
                ]);

            $totalAlterado = $foraBA_2102 + $dentroBA_2102 + $interna_1403 + $foraBA_2403 + $dentroBA_2403 + $semCfop;

            if ($totalAlterado > 0) {
                return response()->json([
                    'status' => 'success', 
                    'message' => "Saneamento completo executado com sucesso!\n\n" .
                                 "• Movidos para Uso/Consumo (2556/1556): " . ($foraBA_2102 + $dentroBA_2102) . " itens.\n" .
                                 "• Ajustados para Consumo com ST (2407/1407): " . ($interna_1403 + $foraBA_2403 + $dentroBA_2403) . " itens.\n" .
                                 "• Corrigidos sem CFOP (1102 CST 051): " . $semCfop . " itens."
                ]);
            }

            return response()->json([
                'status' => 'warning', 
                'message' => 'Nenhuma nota inconsistente pendente de correção encontrada no sistema.'
            ]);
        });
    }
}