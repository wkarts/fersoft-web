<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProsoftExportService;
use App\Models\Compra;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ProsoftExportController extends Controller
{
    protected $prosoftService;

    public function __construct(ProsoftExportService $prosoftService)
    {
        $this->prosoftService = $prosoftService;
    }

    /**
     * Exibe a tela de seleção para exportação com filtro de datas e filial.
     */
    public function index(Request $request)
    {
        $title = 'Exportação Fiscal Prosoft';

        $dataInicio = $request->input('data_inicio', date('Y-m-01'));
        $dataFim = $request->input('data_fim', date('Y-m-t'));

        // Pega a empresa atual e a filial selecionada no filtro
        $empresa_id = __empresa_id_logada() ?? $request->empresa_id;
        $filial_id = $request->input('filial_id');

        // 1. ABA COMPRAS: CFOP diferente de 1933 e sem informação
        $queryCompras = Compra::with(['fornecedor', 'itens'])
            ->where('empresa_id', $empresa_id)
            ->whereBetween('data_emissao', [$dataInicio, $dataFim])
            ->whereHas('itens', function($q) {
                $q->where('cfop_entrada', '!=', '1933')
                    ->where('cfop_entrada', '!=', '2933')
                    ->orWhereNull('cfop_entrada')
                    ->orWhere('cfop_entrada', '');
            });

        // Aplica o filtro de Matriz (NULL) ou Filial (ID)
        if ($filial_id === 'null' || $filial_id === 'matriz') {
            $queryCompras->whereNull('filial_id');
        } elseif (!empty($filial_id)) {
            $queryCompras->where('filial_id', $filial_id);
        }

        $compras = $queryCompras->latest()->get();

        // 2. ABA SERVIÇOS: Apenas CFOP igual a 1933 ou 2933
        $queryServicos = Compra::with(['fornecedor', 'itens'])
            ->where('empresa_id', $empresa_id)
            ->whereBetween('data_emissao', [$dataInicio, $dataFim])
            ->whereHas('itens', function($q) {
                $q->whereIn('cfop_entrada', ['1933', '2933']);
            });

        // Aplica o mesmo filtro de Matriz (NULL) ou Filial (ID)
        if ($filial_id === 'null' || $filial_id === 'matriz') {
            $queryServicos->whereNull('filial_id');
        } elseif (!empty($filial_id)) {
            $queryServicos->where('filial_id', $filial_id);
        }

        $servicos = $queryServicos->latest()->get();

        return view('relatorios.prosoft-export', compact('title', 'compras', 'servicos', 'dataInicio', 'dataFim', 'filial_id'));
    }

    /**
     * Processa a exportação para o Prosoft gerando os arquivos em ZIP.
     */
    public function exportar(Request $request)
    {
        $compraIds = $request->input('compra_ids', []);

        if (empty($compraIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma compra/nota selecionada para exportação.'
            ], 422);
        }

        $exportDir = storage_path('app/public/prosoft_export_' . time());
        if (!File::exists($exportDir)) {
            File::makeDirectory($exportDir, 0755, true);
        }

        $filesGenerated = [];

        // Arquivos de geração (mantidos sem alteração na lógica de chamada)
        $pathNotas = $exportDir . '/notas_entrada.txt';
        $this->prosoftService->gerarArquivoNotas($compraIds, $pathNotas);
        $filesGenerated['notas'] = $pathNotas;

        $pathItens = $exportDir . '/itens_notas.txt';
        $this->prosoftService->gerarArquivoItens($compraIds, $pathItens);
        $filesGenerated['itens'] = $pathItens;

        $pathFaturas = $exportDir . '/faturas.txt';
        $this->prosoftService->gerarArquivoFaturas($compraIds, $pathFaturas);
        $filesGenerated['faturas'] = $pathFaturas;

        $pathItensFaturas = $exportDir . '/itens_faturas.txt';
        $this->prosoftService->gerarArquivoItensFaturas($compraIds, $pathItensFaturas);
        $filesGenerated['itens_faturas'] = $pathItensFaturas;

        $pathServicos = $exportDir . '/servicos_tomados.txt';
        $this->prosoftService->gerarArquivoServicos($compraIds, $pathServicos);
        if (filesize($pathServicos) > 0) {
            $filesGenerated['servicos'] = $pathServicos;
        }

        $pathTerceiros = $exportDir . '/terceiros.txt';
        $this->prosoftService->gerarArquivoTerceiros($compraIds, $pathTerceiros);
        if (filesize($pathTerceiros) > 0) {
            $filesGenerated['terceiros'] = $pathTerceiros;
        }

        $pathProdutos = $exportDir . '/produtos.txt';
        $this->prosoftService->gerarArquivoProdutos($compraIds, $pathProdutos);
        if (filesize($pathProdutos) > 0) {
            $filesGenerated['produtos'] = $pathProdutos;
        }

        $zipFileName = 'prosoft_export_' . date('Ymd_His') . '.zip';
        $zipFilePath = storage_path('app/public/' . $zipFileName);

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($filesGenerated as $filePath) {
                if (File::exists($filePath)) {
                    $zip->addFile($filePath, basename($filePath));
                }
            }
            $zip->close();
        }

        File::deleteDirectory($exportDir);

        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }

    /**
     * Exibe a tela de ajuste fiscal
     */
    public function ajusteFiscal(Request $request)
    {
        $dataInicio = $request->input('data_inicio', date('Y-m-01'));
        $dataFim = $request->input('data_fim', date('Y-m-t'));
        $categoriaId = $request->input('categoria_id');
        $filial_id = $request->input('filial_id');
        $empresa_id = __empresa_id_logada() ?? $request->empresa_id;

        $categorias = \DB::table('categoria_contas')->orderBy('nome')->get();

        $query = \DB::table('item_compras')
            ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
            ->leftJoin('categoria_contas', 'categoria_contas.id', '=', 'compras.categoria_conta_id')
            ->where('compras.empresa_id', $empresa_id)
            ->whereBetween('compras.data_emissao', [$dataInicio, $dataFim]);

        if (!empty($categoriaId)) {
            $query->where('compras.categoria_conta_id', $categoriaId);
        }

        // Filtro de Matriz vs Filial no Ajuste Fiscal
        if ($filial_id === 'null' || $filial_id === 'matriz') {
            $query->whereNull('compras.filial_id');
        } elseif (!empty($filial_id)) {
            $query->where('compras.filial_id', $filial_id);
        }

        $resumo = $query->select(
            'item_compras.cfop_entrada as cfop',
            'item_compras.cst_icms as cst',
            'item_compras.cst_pis as pis',
            'item_compras.cst_cofins as cofins',
            \DB::raw('COALESCE(categoria_contas.nome, "SEM CATEGORIA") as categoria_nome'),
            \DB::raw('COUNT(DISTINCT compras.id) as total_nf'),
            \DB::raw('SUM(item_compras.quantidade * item_compras.valor_unitario) as total_valor')
        )
            ->groupBy('item_compras.cfop_entrada', 'item_compras.cst_icms', 'item_compras.cst_pis', 'item_compras.cst_cofins', 'categoria_contas.nome')
            ->orderBy('total_valor', 'desc')
            ->get();

        return view('prosoft.ajuste_fiscal', compact('resumo', 'categorias', 'dataInicio', 'dataFim', 'categoriaId', 'filial_id'))
            ->with('title', 'Ajuste Fiscal e Resumo em Lote');
    }

    /**
     * Processa a alteração em lote no banco de dados (Ajuste Fiscal)
     */
    public function aplicarAjusteLote(Request $request)
    {
        $empresa_id = __empresa_id_logada() ?? $request->empresa_id;
        $filial_id = $request->input('filial_id');

        $query = \DB::table('item_compras')
            ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
            ->where('compras.empresa_id', $empresa_id);

        // Garante que o Update em Lote não altere dados de outra Filial/Matriz
        if ($filial_id === 'null' || $filial_id === 'matriz') {
            $query->whereNull('compras.filial_id');
        } elseif (!empty($filial_id)) {
            $query->where('compras.filial_id', $filial_id);
        }

        if ($request->filled('filtro_cfop')) {
            $filtroCfop = trim($request->input('filtro_cfop'));
            if ($filtroCfop === 'VAZIO' || $filtroCfop === '') {
                $query->where(function($q) {
                    $q->whereNull('item_compras.cfop_entrada')
                        ->orWhere('item_compras.cfop_entrada', '');
                });
            } else {
                $query->where('item_compras.cfop_entrada', $filtroCfop);
            }
        }

        if ($request->filled('filtro_cst_icms')) {
            $query->where('item_compras.cst_icms', $request->input('filtro_cst_icms'));
        }
        if ($request->filled('filtro_cst_pis')) {
            $query->where('item_compras.cst_pis', $request->input('filtro_cst_pis'));
        }
        if ($request->filled('filtro_categoria_id')) {
            $query->where('compras.categoria_conta_id', $request->input('filtro_categoria_id'));
        }
        if ($request->filled('data_inicio') && $request->filled('data_fim')) {
            $query->whereBetween('compras.data_emissao', [$request->input('data_inicio'), $request->input('data_fim')]);
        }

        $itens = $query->select(
            'item_compras.id as item_id',
            'compras.id as compra_id',
            'compras.nf',
            'compras.numero_emissao',
            'compras.fornecedor_id',
            'item_compras.quantidade',
            'item_compras.valor_unitario',
            'item_compras.cst_pis',
            'item_compras.vbc_pis',
            'item_compras.vbc_icms'
        )->get();

        $comprasAfetadas = [];
        $contadorItens = 0;
        $comprasAtualizadasCategoria = [];

        foreach ($itens as $item) {
            $dataUpdate = [];
            $valorItem = $item->quantidade * $item->valor_unitario;

            if ($request->filled('novo_cfop')) {
                $dataUpdate['cfop_entrada'] = $request->input('novo_cfop');
            }
            if ($request->filled('novo_cst_icms')) {
                $dataUpdate['cst_icms'] = $request->input('novo_cst_icms');
            }

            // Pega o CST efetivo: o novo que está sendo aplicado ou o que já existe no banco
            $cstPisEfetivo = $item->cst_pis;
            if ($request->filled('novo_cst_pis')) {
                $cstPisEfetivo = trim($request->input('novo_cst_pis'));
                $dataUpdate['cst_pis'] = $cstPisEfetivo;
                $dataUpdate['cst_cofins'] = $cstPisEfetivo;
            }

            if ($request->has('ajustar_icms')) {
                $bcIcms = $item->vbc_icms ?? 0;
                if ($bcIcms <= 0) {
                    $aliquotaIcms = $request->input('aliquota_icms', 0);
                    $dataUpdate['p_icms'] = $aliquotaIcms;
                    $dataUpdate['v_icms'] = round($valorItem * ($aliquotaIcms / 100), 2);
                    $dataUpdate['v_outros_icms'] = $valorItem;
                }
            }

            // AJUSTE: Cálculo e Zeramento de PIS/COFINS direto no banco para o SPED (Sem v_receita)
            if ($request->has('calcular_pis_cofins')) {
                if ($cstPisEfetivo === '50') {
                    // Se for 50, aplica as alíquotas e calcula
                    $bc = ($item->vbc_pis > 0) ? $item->vbc_pis : $valorItem;
                    $dataUpdate['p_pis'] = $request->input('aliquota_pis', 1.65);
                    $dataUpdate['p_cofins'] = $request->input('aliquota_cofins', 7.60);
                    $dataUpdate['vbc_pis'] = $bc;
                    $dataUpdate['vbc_cofins'] = $bc;
                    $dataUpdate['v_pis'] = round($bc * ($dataUpdate['p_pis'] / 100), 2);
                    $dataUpdate['v_cofins'] = round($bc * ($dataUpdate['p_cofins'] / 100), 2);
                } else {
                    // Se for 70, 74, etc... Zera as informações de tributação
                    $dataUpdate['p_pis'] = 0;
                    $dataUpdate['p_cofins'] = 0;
                    $dataUpdate['vbc_pis'] = 0;
                    $dataUpdate['vbc_cofins'] = 0;
                    $dataUpdate['v_pis'] = 0;
                    $dataUpdate['v_cofins'] = 0;
                }
            }

            if (!empty($dataUpdate)) {
                \DB::table('item_compras')->where('id', $item->item_id)->update($dataUpdate);
                $contadorItens++;
            }

            if ($request->filled('nova_categoria_id')) {
                \DB::table('compras')->where('id', $item->compra_id)->update([
                    'categoria_conta_id' => $request->input('nova_categoria_id')
                ]);
                $comprasAtualizadasCategoria[$item->compra_id] = true;
            }

            // Apenas para listar no resumo de sucesso
            $compraModel = \App\Models\Compra::with('fornecedor')->find($item->compra_id);
            if ($compraModel) {
                $fornecedorNome = $compraModel->fornecedor->razao_social ?? 'Fornecedor não identificado';
                $numNota = !empty($compraModel->nf) && $compraModel->nf != '0' ? $compraModel->nf : $compraModel->numero_emissao;

                $comprasAfetadas[$item->compra_id] = [
                    'id' => $item->compra_id,
                    'numero' => $numNota,
                    'fornecedor' => $fornecedorNome
                ];
            }
        }

        $detalhesNotas = implode(', ', array_map(function($c) {
            return "NF {$c['numero']} ({$c['fornecedor']})";
        }, $comprasAfetadas));

        $msgCategoria = !empty($request->input('nova_categoria_id')) ? " e categoria atribuída em " . count($comprasAtualizadasCategoria) . " nota(s)" : "";
        $mensagem = "Ajuste aplicado com sucesso! Total de {$contadorItens} itens atualizados{$msgCategoria} [ {$detalhesNotas} ].";

        return redirect()->back()->with('mensagem_sucesso', $mensagem);
    }

    /**
     * Faz a auditoria e recálculo automático de PIS/COFINS no período
     */
    public function auditarPisCofinsPeriodo(Request $request)
    {
        $empresa_id = __empresa_id_logada() ?? $request->empresa_id;
        $filial_id = $request->input('filial_id');
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        if (!$dataInicio || !$dataFim) {
            return redirect()->back()->with('error', 'Por favor, informe a data inicial e final para a auditoria.');
        }

        $query = \DB::table('item_compras')
            ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
            ->where('compras.empresa_id', $empresa_id)
            ->whereBetween('compras.data_emissao', [$dataInicio, $dataFim]);

        // Respeita o filtro de Matriz/Filial
        if ($filial_id === 'null' || $filial_id === 'matriz') {
            $query->whereNull('compras.filial_id');
        } elseif (!empty($filial_id)) {
            $query->where('compras.filial_id', $filial_id);
        }

        // Pega todos os itens do período
        $itens = $query->select(
            'item_compras.id as item_id',
            'item_compras.cst_pis',
            'item_compras.quantidade',
            'item_compras.valor_unitario',
            'item_compras.vbc_pis'
        )->get();

        $contadorAtualizados = 0;

        foreach ($itens as $item) {
            $valorItem = $item->quantidade * $item->valor_unitario;
            $cstPis = trim($item->cst_pis);
            $dataUpdate = [];

            if ($cstPis === '50') {
                // É tributado: Aplica alíquotas e calcula os valores (Sem v_receita)
                $bc = ($item->vbc_pis > 0) ? $item->vbc_pis : $valorItem;
                $dataUpdate['p_pis'] = 1.65;
                $dataUpdate['p_cofins'] = 7.60;
                $dataUpdate['vbc_pis'] = $bc;
                $dataUpdate['vbc_cofins'] = $bc;
                $dataUpdate['v_pis'] = round($bc * 0.0165, 2);
                $dataUpdate['v_cofins'] = round($bc * 0.0760, 2);
            } else {
                // Não é 50 (ex: 70, 74, etc): Zera tudo (Sem v_receita)
                $dataUpdate['p_pis'] = 0;
                $dataUpdate['p_cofins'] = 0;
                $dataUpdate['vbc_pis'] = 0;
                $dataUpdate['vbc_cofins'] = 0;
                $dataUpdate['v_pis'] = 0;
                $dataUpdate['v_cofins'] = 0;
            }

            // Executa a atualização direta no banco
            \DB::table('item_compras')->where('id', $item->item_id)->update($dataUpdate);
            $contadorAtualizados++;
        }

        $mensagem = "Auditoria concluída com sucesso! {$contadorAtualizados} itens validados e recalculados automaticamente para o período de " . date('d/m/Y', strtotime($dataInicio)) . " a " . date('d/m/Y', strtotime($dataFim)) . ".";

        return redirect()->back()->with('mensagem_sucesso', $mensagem);
    }

    /**
     * Tela de visualização da Apuração de PIS e COFINS (Lendo CST do XML de saída)
     */
    public function apuracaoPisCofins(Request $request)
    {
        $title = 'Apuração de PIS e COFINS';

        $dataInicio = $request->input('data_inicio', date('Y-m-01')) . ' 00:00:00';
        $dataFim = $request->input('data_fim', date('Y-m-t')) . ' 23:59:59';

        $viewDataInicio = date('Y-m-d', strtotime($dataInicio));
        $viewDataFim = date('Y-m-d', strtotime($dataFim));

        $empresa_id = __empresa_id_logada() ?? $request->empresa_id;
        $filial_id = $request->input('filial_id');

        // -------------------------------------------------------------
        // 1. CRÉDITOS / AQUISIÇÕES (Entradas / Compras do banco)
        // -------------------------------------------------------------
        $queryCompras = \DB::table('item_compras')
            ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
            ->where('compras.empresa_id', $empresa_id)
            ->whereBetween('compras.data_emissao', [$dataInicio, $dataFim]);

        if ($filial_id === 'null' || $filial_id === 'matriz') {
            $queryCompras->whereNull('compras.filial_id');
        } elseif (!empty($filial_id)) {
            $queryCompras->where('compras.filial_id', $filial_id);
        }

        $comprasAgrupadas = $queryCompras->select(
            \DB::raw('"Compra (Entrada)" as tipo'),
            'item_compras.cfop_entrada as cfop',
            \DB::raw('COALESCE(item_compras.cst_pis, "50") as cst'),
            \DB::raw('COALESCE(SUM(item_compras.quantidade * item_compras.valor_unitario), 0) as valor_total_item'),
            \DB::raw('COALESCE(SUM(item_compras.vbc_pis), 0) as bc_pis'),
            \DB::raw('COALESCE(SUM(item_compras.vbc_cofins), 0) as bc_cofins'),
            \DB::raw('COALESCE(SUM(CASE WHEN item_compras.cst_pis = "50" THEN item_compras.v_pis ELSE 0 END), 0) as valor_pis'),
            \DB::raw('COALESCE(SUM(CASE WHEN item_compras.cst_pis = "50" THEN item_compras.v_cofins ELSE 0 END), 0) as valor_cofins')
        )
            ->groupBy('item_compras.cfop_entrada', 'item_compras.cst_pis')
            ->get();

        // -------------------------------------------------------------
        // 2. DÉBITOS / RECEITAS (Processando Vendas e lendo XMLs)
        // -------------------------------------------------------------
        $queryVendas = \DB::table('vendas')
            ->where('empresa_id', $empresa_id)
            ->whereBetween('data_emissao', [$dataInicio, $dataFim]);

        if ($filial_id === 'null' || $filial_id === 'matriz') {
            $queryVendas->whereNull('filial_id');
        } elseif (!empty($filial_id)) {
            $queryVendas->where('filial_id', $filial_id);
        }

        $vendasList = $queryVendas->select('id', 'path_xml')->get();
        $vendasProcessadas = [];

        foreach ($vendasList as $venda) {
            if (!empty($venda->path_xml)) {
                // Tenta localizar o arquivo XML no storage ou public path
                $xmlPath = storage_path('app/' . $venda->path_xml);
                if (!file_exists($xmlPath)) {
                    $xmlPath = public_path($venda->path_xml);
                }

                if (file_exists($xmlPath)) {
                    $xmlContent = @file_get_contents($xmlPath);
                    if ($xmlContent) {
                        $xml = @simplexml_load_string($xmlContent);
                        if ($xml && isset($xml->NFe->infNFe->det)) {
                            foreach ($xml->NFe->infNFe->det as $det) {
                                $cfop = (string)($det->prod->CFOP ?? '5102');
                                $vProd = (float)($det->prod->vProd ?? 0);

                                // Extrai o CST de PIS do XML (várias tags possíveis na NFe)
                                $cst = '01'; // Padrão caso não ache tag específica
                                if (isset($det->imposto->PIS)) {
                                    $pis = $det->imposto->PIS;
                                    if (isset($pis->PISAliq->CST)) $cst = (string)$pis->PISAliq->CST;
                                    elseif (isset($pis->PISQtde->CST)) $cst = (string)$pis->PISQtde->CST;
                                    elseif (isset($pis->PISNT->CST)) $cst = (string)$pis->PISNT->CST;
                                    elseif (isset($pis->PISOutr->CST)) $cst = (string)$pis->PISOutr->CST;
                                }

                                $cst = str_pad(trim($cst), 2, '0', STR_PAD_LEFT);

                                // Calcula imposto apenas se CST for tributado (01 ou 02)
                                $vPis = in_array($cst, ['01', '02']) ? round($vProd * 0.0165, 2) : 0;
                                $vCofins = in_array($cst, ['01', '02']) ? round($vProd * 0.0760, 2) : 0;

                                $key = $cfop . '_' . $cst;
                                if (!isset($vendasProcessadas[$key])) {
                                    $vendasProcessadas[$key] = [
                                        'tipo' => 'Venda (NF-e)',
                                        'cfop' => $cfop,
                                        'cst' => $cst,
                                        'valor_total_item' => 0,
                                        'bc_pis' => 0,
                                        'bc_cofins' => 0,
                                        'valor_pis' => 0,
                                        'valor_cofins' => 0
                                    ];
                                }

                                $vendasProcessadas[$key]['valor_total_item'] += $vProd;
                                $vendasProcessadas[$key]['bc_pis'] += $vProd;
                                $vendasProcessadas[$key]['bc_cofins'] += $vProd;
                                $vendasProcessadas[$key]['valor_pis'] += $vPis;
                                $vendasProcessadas[$key]['valor_cofins'] += $vCofins;
                            }
                        }
                    }
                }
            }
        }

        // Converte o array processado em collection para a view
        $vendasAgrupadas = collect(array_values($vendasProcessadas));

        // -------------------------------------------------------------
        // 3. DÉBITOS / CT-E (Conhecimentos de Transporte)
        // -------------------------------------------------------------
        $queryCtes = \DB::table('ctes')
            ->where('empresa_id', $empresa_id)
            ->whereBetween('data_emissao', [$dataInicio, $dataFim]);

        if ($filial_id === 'null' || $filial_id === 'matriz') {
            $queryCtes->whereNull('filial_id');
        } elseif (!empty($filial_id)) {
            $queryCtes->where('filial_id', $filial_id);
        }

        $ctesAgrupados = $queryCtes->select(
            \DB::raw('"CT-e (Transporte)" as tipo'),
            \DB::raw('"3353" as cfop'),
            \DB::raw('COALESCE(cst, "01") as cst'),
            \DB::raw('COALESCE(SUM(valor_receber), 0) as valor_total_item'),
            \DB::raw('COALESCE(SUM(valor_receber), 0) as bc_pis'),
            \DB::raw('COALESCE(SUM(valor_receber), 0) as bc_cofins'),
            \DB::raw('COALESCE(SUM(valor_receber * 0.0165), 0) as valor_pis'),
            \DB::raw('COALESCE(SUM(valor_receber * 0.0760), 0) as valor_cofins')
        )
            ->groupBy('cst')
            ->get();

        // -------------------------------------------------------------
        // 4. TOTALIZADORES GERAIS
        // -------------------------------------------------------------
        $totalDebitoPis = $vendasAgrupadas->sum('valor_pis') + $ctesAgrupados->sum('valor_pis');
        $totalDebitoCofins = $vendasAgrupadas->sum('valor_cofins') + $ctesAgrupados->sum('valor_cofins');

        $totalCreditoPis = $comprasAgrupadas->where('cst', '50')->sum('valor_pis');
        $totalCreditoCofins = $comprasAgrupadas->where('cst', '50')->sum('valor_cofins');

        $saldoPis = $totalDebitoPis - $totalCreditoPis;
        $saldoCofins = $totalDebitoCofins - $totalCreditoCofins;

        $apuracao = [
            'compras' => $comprasAgrupadas,
            'vendas' => $vendasAgrupadas,
            'ctes' => $ctesAgrupados,
            'total_debito_pis' => $totalDebitoPis,
            'total_debito_cofins' => $totalDebitoCofins,
            'total_credito_pis' => $totalCreditoPis,
            'total_credito_cofins' => $totalCreditoCofins,
            'saldo_pis' => $saldoPis,
            'saldo_cofins' => $saldoCofins,
        ];

        $dataInicio = $viewDataInicio;
        $dataFim = $viewDataFim;

        return view('prosoft.apuracao_pis_cofins', compact('title', 'dataInicio', 'dataFim', 'filial_id', 'apuracao'));
    }
}
