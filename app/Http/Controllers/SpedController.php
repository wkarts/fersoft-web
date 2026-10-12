<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use NFePHP\EFD\Elements\ICMSIPI\Z0000;
use NFePHP\EFD\Elements\ICMSIPI\Z0001;
use NFePHP\EFD\Elements\ICMSIPI\Z0005;
use NFePHP\EFD\Elements\ICMSIPI\Z0100;
use NFePHP\EFD\Elements\ICMSIPI\Z0150;
use NFePHP\EFD\Elements\ICMSIPI\Z0190;
use NFePHP\EFD\Elements\ICMSIPI\Z0200;
use NFePHP\EFD\Elements\ICMSIPI\C001;
use NFePHP\EFD\Elements\ICMSIPI\C100;
use NFePHP\EFD\Elements\ICMSIPI\C170;
use NFePHP\EFD\Elements\ICMSIPI\C190;
use NFePHP\EFD\Elements\ICMSIPI\C500;
use NFePHP\EFD\Elements\ICMSIPI\C590;
use NFePHP\EFD\Elements\ICMSIPI\D001;
use NFePHP\EFD\Elements\ICMSIPI\D100;
use NFePHP\EFD\Elements\ICMSIPI\D190;
use NFePHP\EFD\Elements\ICMSIPI\E001;
use NFePHP\EFD\Elements\ICMSIPI\E100;
use NFePHP\EFD\Elements\ICMSIPI\E110;
use NFePHP\EFD\Elements\ICMSIPI\E111;
use NFePHP\EFD\Elements\ICMSIPI\E113;
use NFePHP\EFD\Elements\ICMSIPI\E116;
use NFePHP\EFD\Elements\ICMSIPI\H001;
use NFePHP\EFD\Elements\ICMSIPI\H005;
use NFePHP\EFD\Elements\ICMSIPI\H010;
use NFePHP\EFD\Elements\ICMSIPI\H020;
use NFePHP\EFD\Elements\ICMSIPI\H030;
use NFePHP\EFD\Elements\ICMSIPI\K001;
use NFePHP\EFD\Elements\ICMSIPI\K100;
use NFePHP\EFD\Elements\ICMSIPI\K200;
use App\Models\ConfigNota;
use App\Models\EscritorioContabil;
use App\Models\Venda;
use App\Models\Estoque;
use App\Models\VendaCaixa;
use App\Models\Compra;
use App\Models\Cte;
use App\Models\SpedConfig;
use App\Models\Devolucao;
use App\Models\Transferencia;
use App\Models\RemessaNfe;
use App\Services\SpeedService;
use App\Utils\SpedUtil;

class SpedController extends Controller
{
    protected $empresa_id = null;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            return $next($request);
        });
    }

    public function index()
    {
        $date = date('d/m/Y');
        $firstDate = date('Y-m-01');
        $lastDate = date('Y-m-t');

        $filiais = \App\Models\Filial::where('empresa_id', $this->empresa_id)->get();
        return view('sped.index', compact('firstDate', 'lastDate', 'filiais'));
    }

    public function store(Request $request){

        $sped = '';
        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $inventario = $request->inventario;
        $dataInventario = $request->data_inventario;
        $motivoInventario = $request->motivo_inventario;

        $filial_id = $request->filial_id;
        $isFilial = !empty($filial_id);

        $dInicial = \Carbon\Carbon::parse($dataInicial)->format('dmY');
        $dFinal = \Carbon\Carbon::parse($dataFinal)->format('dmY');
        $mesRef = \Carbon\Carbon::parse($dataFinal)->format('mY');

        if ($isFilial) {
            $config = \App\Models\Filial::find($filial_id);
        } else {
            $config = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();
        }

        $cnpjEmpresa = preg_replace('/[^0-9]/', '', $config->cnpj);
        $codMunEmitente = $config->codMun ?? ($config->cidade->codigo ?? '');

        $spedConfig = \App\Models\SpedConfig::where('empresa_id', $this->empresa_id)
            ->when(!$isFilial, function($q) { return $q->whereNull('filial_id'); })
            ->when($isFilial, function($q) use ($filial_id) { return $q->where('filial_id', $filial_id); })
            ->first();

        // BLOCO 0000
        $std = new \stdClass();
        $std->COD_VER = '020';
        $std->COD_FIN = '0';
        $std->DT_INI = $dInicial;
        $std->DT_FIN = $dFinal;
        $std->NOME = $config->razao_social;
        $std->CNPJ = $cnpjEmpresa;
        $std->CPF = '';
        $std->UF = $config->UF ?? $config->uf;
        $std->IE = $config->ie;
        $std->COD_MUN = $codMunEmitente;
        $std->IM = '';
        $std->SUFRAMA = '';
        $std->IND_PERFIL = $spedConfig->perfil ?? 'B';
        $std->IND_ATIV = $spedConfig->ind_ativ ?? '1';

        try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\Z0000($std) . "\r\n"; } catch (\Exception $e) {}

        // BLOCO 0001
        $std = new \stdClass();
        $std->IND_MOV = '0';
        try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\Z0001($std) . "\r\n"; } catch (\Exception $e) {}

        // BLOCO 0005
        $std = new \stdClass();
        $std->FANTASIA = $config->nome_fantasia ?? $config->razao_social;
        $std->CEP = preg_replace('/[^0-9]/', '', $config->cep);
        $std->END = $config->logradouro ?? $config->rua;
        $std->NUM = $config->numero;
        $std->COMPL = $config->complemento;
        $std->BAIRRO = $config->bairro;
        $std->FONE = preg_replace('/[^0-9]/', '', $config->fone ?? $config->telefone ?? '');
        $std->EMAIL = $config->email;
        try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\Z0005($std) . "\r\n"; } catch (\Exception $e) {}

        // BLOCO 0100
        $contador = \App\Models\EscritorioContabil::where('empresa_id', $this->empresa_id)->first();
        if($contador == null){
            session()->flash("mensagem_erro", "Configure o contador primeiro!");
            return redirect('/escritorio');
        }
        $std = new \stdClass();
        $std->NOME = $contador->razao_social;
        $std->CNPJ = preg_replace('/[^0-9]/', '', $contador->cnpj);
        if($contador->cpf != "") $std->CPF = preg_replace('/[^0-9]/', '', $contador->cpf);
        $std->CRC = $contador->crc;
        $std->CEP = preg_replace('/[^0-9]/', '', $contador->cep);
        $std->END = $contador->logradouro;
        $std->NUM = $contador->numero;
        $std->BAIRRO = $contador->bairro;
        $std->FONE = preg_replace('/[^0-9]/', '', $contador->fone);
        $std->EMAIL = $contador->email;
        $std->COD_MUN = $contador->cidade->codigo ?? '';
        try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\Z0100($std) . "\r\n"; } catch (\Exception $e) {}

        // ==========================================
        // 1. BUSCA DE NOTAS E CT-ES NO BANCO DE DADOS
        // ==========================================

        $vendas = \App\Models\Venda::whereDate('data_emissao', '>=', $dataInicial)
            ->whereDate('data_emissao', '<=', $dataFinal)
            ->whereIn('estado', ['APROVADO', 'aprovado', '1'])
            ->where('empresa_id', $this->empresa_id)
            ->when(!$isFilial, function($q) { return $q->whereNull('filial_id'); })
            ->when($isFilial, function($q) use ($filial_id) { return $q->where('filial_id', $filial_id); })
            ->get();

        $vendasPdv = \App\Models\VendaCaixa::whereDate('data_emissao', '>=', $dataInicial)
            ->whereDate('data_emissao', '<=', $dataFinal)
            ->whereIn('estado', ['APROVADO', 'aprovado', '1'])
            ->where('empresa_id', $this->empresa_id)
            ->when(!$isFilial, function($q) { return $q->whereNull('filial_id'); })
            ->when($isFilial, function($q) use ($filial_id) { return $q->where('filial_id', $filial_id); })
            ->get();

        $comprasProprias = \App\Models\Compra::whereDate('data_emissao', '>=', $dataInicial)
            ->whereDate('data_emissao', '<=', $dataFinal)
            ->where(function($q) {
                $q->where('xml_importado', 0)->orWhereNull('xml_importado');
            })
            ->whereIn('estado', ['APROVADO', 'aprovado', '1'])
            ->where('empresa_id', $this->empresa_id)
            ->when(!$isFilial, function($q) { return $q->whereNull('filial_id'); })
            ->when($isFilial, function($q) use ($filial_id) { return $q->where('filial_id', $filial_id); })
            ->get();

        $comprasImportadas = \App\Models\Compra::whereDate('data_emissao', '>=', $dataInicial)
            ->whereDate('data_emissao', '<=', $dataFinal)
            ->where('xml_importado', 1)
            ->whereIn('estado', ['IMPORTADO', 'importado'])
            ->where('empresa_id', $this->empresa_id)
            ->when(!$isFilial, function($q) { return $q->whereNull('filial_id'); })
            ->when($isFilial, function($q) use ($filial_id) { return $q->where('filial_id', $filial_id); })
            ->get();

        $devolucoes = \App\Models\Devolucao::whereDate('created_at', '>=', $dataInicial)
            ->whereDate('created_at', '<=', $dataFinal)
            ->whereIn('estado', ['1', '2', '3'])
            ->where('empresa_id', $this->empresa_id)
            ->when(!$isFilial, function($q) { return $q->whereNull('filial_id'); })
            ->when($isFilial, function($q) use ($filial_id) { return $q->where('filial_id', $filial_id); })
            ->get();

        $transferencias = \App\Models\Transferencia::whereDate('created_at', '>=', $dataInicial)
            ->whereDate('created_at', '<=', $dataFinal)
            ->whereIn('estado', ['aprovado', '1'])
            ->where('empresa_id', $this->empresa_id)
            ->when(!$isFilial, function($q) { return $q->whereNull('filial_saida_id'); })
            ->when($isFilial, function($q) use ($filial_id) { return $q->where('filial_saida_id', $filial_id); })
            ->get();

        $remessas = \App\Models\RemessaNfe::whereDate('created_at', '>=', $dataInicial)
            ->whereDate('created_at', '<=', $dataFinal)
            ->whereIn('estado', ['aprovado', '1'])
            ->where('empresa_id', $this->empresa_id)
            ->when(!$isFilial, function($q) { return $q->whereNull('filial_id'); })
            ->when($isFilial, function($q) use ($filial_id) { return $q->where('filial_id', $filial_id); })
            ->get();

        $ctesEmitidos = \App\Models\Cte::whereDate('data_emissao', '>=', $dataInicial)
            ->whereDate('data_emissao', '<=', $dataFinal)
            ->whereIn('estado', ['APROVADO', 'aprovado', '1'])
            ->where('empresa_id', $this->empresa_id)
            ->when(!$isFilial, function($q) { return $q->whereNull('filial_id'); })
            ->when($isFilial, function($q) use ($filial_id) { return $q->where('filial_id', $filial_id); })
            ->get();

        // ==========================================
        // 2. PRÉ-PROCESSAMENTO: FILTROS E COLETA DE DADOS
        // ==========================================
        $speedService = new \App\Services\SpeedService($config);

        $somaICMS = 0;
        $somaCreditos = 0;

        $regras1400 = \App\Models\SpedRegra1400::where('empresa_id', $this->empresa_id)
            ->when(!$isFilial, function($q) { return $q->whereNull('filial_id'); })
            ->when($isFilial, function($q) use ($filial_id) { return $q->where('filial_id', $filial_id); })
            ->pluck('codigo_ipm', 'cfop')
            ->toArray();
        $totais1400 = [];

        $notasValidasXml = [];
        $notasValidasBanco = [];
        $ctesValidosXml = [];
        $participantesUsados = [];
        $produtosUsados = [];
        $unidadesUsadas = [];
        $unidadesDiferentes = [];

        $extractDocPart = function($dest, $idFallback) {
            if (!$dest) return null;
            $doc = preg_replace('/[^0-9]/', '', (string)($dest->CNPJ ?? $dest->CPF ?? ''));
            if (empty($doc) || $doc == '00000000000000') {
                $idEstrangeiro = (string)($dest->idEstrangeiro ?? '');
                if (!empty($idEstrangeiro)) {
                    return 'EX' . preg_replace('/[^A-Za-z0-9]/', '', $idEstrangeiro);
                }
                return 'EX' . $idFallback;
            }
            return $doc;
        };

        // --- FILTRAR TODOS OS XMLS ---
        foreach([
                    ['data' => $vendas, 'path' => 'xml_nfe/', 'tipo' => 'venda'],
                    ['data' => $vendasPdv, 'path' => 'xml_nfce/', 'tipo' => 'pdv'],
                    ['data' => $comprasProprias, 'path' => 'xml_entrada_emitida/', 'tipo' => 'compra'],
                    ['data' => $devolucoes, 'path' => 'xml_devolucao/', 'tipo' => 'devolucao'],
                    ['data' => $remessas, 'path' => 'xml_nfe/', 'tipo' => 'remessa'],
                    ['data' => $transferencias, 'path' => 'xml_nfe/', 'tipo' => 'transferencia']
                ] as $source){
            foreach($source['data'] as $v){
                $xml = null;
                $chave = preg_replace('/[^0-9]/', '', $v->chave ?? $v->chave_gerada ?? '');

                $pastaBase = $source['path'];
                if ($source['tipo'] == 'devolucao' && isset($v->tipo) && $v->tipo == 0) {
                    $pastaBase = 'xml_devolucao_entrada/';
                }

                if (!empty($chave)) {
                    $pastasParaBuscar = [$pastaBase, 'xml_nfe/', 'xml_entrada_emitida/', 'xml_devolucao_entrada/', 'xml_devolucao/'];
                    foreach ($pastasParaBuscar as $pasta) {
                        $caminho = public_path($pasta . $chave . '.xml');
                        if (file_exists($caminho)) {
                            $xmlStr = file_get_contents($caminho);
                            $xml = @simplexml_load_string($xmlStr);
                            if ($xml) break;
                        }
                    }
                }

                if (!$xml) {
                    try { $xml = $speedService->getXml($v, $pastaBase); } catch (\Exception $e) {}
                }

                if($xml != null){
                    $itens = $speedService->getItemNfe($xml);
                    $temProdutoValido = false;
                    foreach($itens as $item){
                        $cfop = (string)($item->prod->CFOP ?? '1102');
                        if($cfop == '2404') $cfop = '2403';
                        if(!in_array($cfop, ['1933', '2933'])){
                            $temProdutoValido = true;
                            $unL = strtoupper(trim((string)$item->prod->uCom));
                            if(!empty($unL)) $unidadesUsadas[$unL] = true;
                        }
                    }
                    if($temProdutoValido){
                        $dest = $speedService->getDestinatario($xml);
                        $doc = $extractDocPart($dest, $v->id ?? rand(1000,9999));

                        if($doc) {
                            $participantesUsados[$doc] = $dest;
                            $v->doc_part_ext = $doc;
                        }

                        $notasValidasXml[] = ['xml' => $xml, 'tipo' => $source['tipo'], 'obj' => $v];
                    }
                }
                else if ($source['tipo'] == 'compra') {
                    $itensFiltrados = [];
                    if (isset($v->itens)) {
                        foreach($v->itens as $item){
                            $cfop = (string)($item->cfop_entrada ?? '1102');
                            if(!in_array($cfop, ['1933', '2933'])){
                                $item->cfop_entrada = $cfop;
                                $itensFiltrados[] = $item;
                                $pDb = $item->produto;
                                if ($pDb) {
                                    $unBase = strtoupper(trim((string)($pDb->unidade_venda ?? $pDb->unidade ?? 'UN')));
                                    $unBase = preg_replace('/[^A-Z0-9]/', '', $unBase);
                                    if (empty($unBase) || $unBase == 'P') $unBase = 'UN';

                                    $unXml = strtoupper(trim((string)($item->unidade_compra ?? 'UN')));
                                    $unXml = preg_replace('/[^A-Z0-9]/', '', $unXml);
                                    if (empty($unXml) || $unXml == 'P') $unXml = 'UN';

                                    $produtosUsados[(string)$item->produto_id] = [
                                        'descr' => (string)$pDb->nome,
                                        'unid'  => substr($unBase, 0, 6),
                                        'ncm'   => preg_replace('/[^0-9]/', '', $pDb->NCM ?? ''),
                                        'tipo'  => str_pad($pDb->tipo_item ?? '00', 2, "0", STR_PAD_LEFT)
                                    ];

                                    if (substr($unXml, 0, 6) !== substr($unBase, 0, 6)) {
                                        $unidadesDiferentes[(string)$item->produto_id][substr($unXml, 0, 6)] = true;
                                    }

                                    $unidadesUsadas[substr($unBase, 0, 6)] = true;
                                    $unidadesUsadas[substr($unXml, 0, 6)] = true;
                                }
                            }
                        }
                        if(count($itensFiltrados) > 0){
                            $v->itens_para_sped = $itensFiltrados;
                            $notasValidasBanco[] = $v;
                            if (isset($v->fornecedor)) {
                                $docForn = $extractDocPart($v->fornecedor, $v->fornecedor->id ?? rand(1000,9999));
                                if($docForn) {
                                    $participantesUsados[$docForn] = $v->fornecedor;
                                    $v->doc_part_ext = $docForn;
                                }
                            }
                        }
                    }
                }
            }
        }

        // --- PROCESSAR XMLS CTE ---
        foreach($ctesEmitidos as $cte){
            $path = public_path('xml_cte/' . $cte->chave . '.xml');
            if(file_exists($path)){
                $xmlCteStr = file_get_contents($path);
                $xmlCteStr = str_replace('xmlns="http://www.portalfiscal.inf.br/cte"', '', $xmlCteStr);
                $xmlCte = @simplexml_load_string($xmlCteStr);
                if($xmlCte){
                    $inf = null;
                    if (isset($xmlCte->CTe->infCte)) { $inf = $xmlCte->CTe->infCte; }
                    elseif (isset($xmlCte->infCte)) { $inf = $xmlCte->infCte; }

                    if($inf){
                        $ctesValidosXml[] = $inf;
                        $tomaNode = null;
                        if(isset($inf->ide->toma3)) {
                            $idxToma = (string)$inf->ide->toma3->toma;
                            $tagsToma = ['rem','exped','receb','dest'];
                            $tomaNode = $inf->{$tagsToma[$idxToma] ?? 'dest'};
                        } elseif(isset($inf->ide->toma4)) {
                            $tomaNode = $inf->ide->toma4;
                        }

                        if($tomaNode){
                            $docToma = $extractDocPart($tomaNode, $cte->id);
                            if($docToma) { $participantesUsados[$docToma] = $tomaNode; }
                        }
                    }
                }
            }
        }

        // --- FILTRAR COMPRAS DE TERCEIROS (BANCO) ---
        foreach($comprasImportadas as $compra){
            $itensFiltrados = [];
            foreach($compra->itens as $item){
                $cfop = (string)($item->cfop_entrada ?? '1102');
                if(!in_array($cfop, ['1933', '2933'])){
                    $item->cfop_entrada = $cfop;
                    $itensFiltrados[] = $item;
                    $pDb = $item->produto;
                    if ($pDb) {
                        $unBase = strtoupper(trim((string)($pDb->unidade_venda ?? $pDb->unidade ?? 'UN')));
                        $unBase = preg_replace('/[^A-Z0-9]/', '', $unBase);
                        if (empty($unBase) || $unBase == 'P') $unBase = 'UN';

                        $unXml = strtoupper(trim((string)($item->unidade_compra ?? 'UN')));
                        $unXml = preg_replace('/[^A-Z0-9]/', '', $unXml);
                        if (empty($unXml) || $unXml == 'P') $unXml = 'UN';

                        $produtosUsados[(string)$item->produto_id] = [
                            'descr' => (string)$pDb->nome,
                            'unid'  => substr($unBase, 0, 6),
                            'ncm'   => preg_replace('/[^0-9]/', '', $pDb->NCM ?? ''),
                            'tipo'  => str_pad($pDb->tipo_item ?? '00', 2, "0", STR_PAD_LEFT)
                        ];

                        if (substr($unXml, 0, 6) !== substr($unBase, 0, 6)) {
                            $unidadesDiferentes[(string)$item->produto_id][substr($unXml, 0, 6)] = true;
                        }

                        $unidadesUsadas[substr($unBase, 0, 6)] = true;
                        $unidadesUsadas[substr($unXml, 0, 6)] = true;
                    }
                }
            }
            if(count($itensFiltrados) > 0){
                $compra->itens_para_sped = $itensFiltrados;
                $notasValidasBanco[] = $compra;

                $docForn = preg_replace('/[^0-9]/', '', $compra->fornecedor->cpf_cnpj ?? '');
                if($docForn && $docForn != '00000000000000') {
                    $participantesUsados[$docForn] = $compra->fornecedor;
                    $compra->doc_part_ext = $docForn;
                }
            }
        }

        // ======= INÍCIO: BUSCA DO DIFAL E INJEÇÃO NO 0150 =======
        $difalItems = collect([]);
        $totalDifal = 0;
        $difalAgrupado = collect([]);

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('apuracao_difal_itens') && \Illuminate\Support\Facades\Schema::hasTable('apuracoes_difal')) {

                $difalItems = \Illuminate\Support\Facades\DB::table('apuracao_difal_itens')
                    ->join('apuracoes_difal', 'apuracoes_difal.id', '=', 'apuracao_difal_itens.apuracao_difal_id')
                    ->join('compras', 'compras.id', '=', 'apuracao_difal_itens.nota_fiscal_id')
                    ->leftJoin('fornecedors', 'fornecedors.id', '=', 'compras.fornecedor_id')
                    ->where('apuracoes_difal.empresa_id', $this->empresa_id)
                    ->when(empty($filial_id), function($q) {
                        return $q->whereNull('apuracoes_difal.filial_id');
                    })
                    ->when(!empty($filial_id), function($q) use ($filial_id) {
                        return $q->where('apuracoes_difal.filial_id', $filial_id);
                    })
                    ->whereDate('compras.data_emissao', '>=', $dataInicial)
                    ->whereDate('compras.data_emissao', '<=', $dataFinal)
                    ->select(
                        'apuracao_difal_itens.*',
                        'compras.chave',
                        'compras.data_emissao',
                        'compras.nf',
                        'fornecedors.cpf_cnpj as doc_fornecedor'
                    )
                    ->get();

                $difalAgrupado = $difalItems->groupBy('nota_fiscal_id')->map(function ($itensDaNota) {
                    $primeiroItem = $itensDaNota->first();
                    $primeiroItem->valor_difal = round($itensDaNota->sum('valor_difal'), 2);
                    return $primeiroItem;
                });

                $totalDifal = round($difalAgrupado->sum('valor_difal'), 2);

                foreach ($difalAgrupado as $itemDifal) {
                    $docDifal = preg_replace('/[^0-9]/', '', (string)($itemDifal->doc_fornecedor ?? ''));
                    if (!empty($docDifal) && $docDifal != '00000000000000' && !isset($participantesUsados[$docDifal])) {
                        $fornDb = \App\Models\Fornecedor::where('cpf_cnpj', 'like', "%{$docDifal}%")->first();
                        if ($fornDb) {
                            $participantesUsados[$docDifal] = $fornDb;
                        } else {
                            $stdP = new \stdClass();
                            $stdP->razao_social = 'FORNECEDOR DIFAL ' . $docDifal;
                            $stdP->rua = 'NAO INFORMADO';
                            $stdP->numero = 'S/N';
                            $stdP->bairro = 'NAO INFORMADO';
                            $participantesUsados[$docDifal] = $stdP;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $sped .= "|ERRO-DB-DIFAL|" . $e->getMessage() . "|\r\n";
        }
        // ======= FIM: BUSCA DO DIFAL =======

        // ==========================================
        // 3. BLOCO 0150, 0190, 0200
        // ==========================================
        foreach($participantesUsados as $doc => $p){
            $std = new \stdClass();
            $std->COD_PART = $doc;
            $std->NOME = strtoupper(substr((string)($p->xNome ?? $p->razao_social ?? 'CONSUMIDOR'), 0, 100));
            $std->IE = preg_replace('/[^0-9]/', '', (string)($p->IE ?? $p->ie_rg ?? ''));
            if(empty($std->IE) || strtoupper($std->IE) == 'ISENTO') $std->IE = '';

            $std->END = strtoupper(substr((string)($p->enderDest->xLgr ?? $p->rua ?? 'S/N'), 0, 60));
            $std->NUM = (string)($p->enderDest->nro ?? $p->numero ?? 'S/N');
            $std->BAIRRO = strtoupper(substr((string)($p->enderDest->xBairro ?? $p->bairro ?? 'S/B'), 0, 60));

            if (str_starts_with($doc, 'EX')) {
                $std->COD_PAIS = (string)($p->enderDest->cPais ?? '999');
                if(empty($std->COD_PAIS) || $std->COD_PAIS == '1058') $std->COD_PAIS = '999';
                $std->CNPJ = '';
                $std->CPF = '';
                $std->COD_MUN = '9999999';
            } else {
                $std->COD_PAIS = '1058';
                if(strlen($doc) == 11) { $std->CPF = $doc; $std->CNPJ = ''; } else { $std->CNPJ = $doc; $std->CPF = ''; }

                $cMunRaw = (string)($p->enderDest->cMun ?? $p->cidade->codigo ?? '');

                if ($cMunRaw == '1720259') {
                    $cMunRaw = '2927408';
                }
                if ($cMunRaw == '1303908') {
                    $cMunRaw = '3550308';
                }
                if ($cMunRaw == '1303601') {
                    $cMunRaw = '4122305';
                }

                $std->COD_MUN = $cMunRaw;
                if(empty($std->COD_MUN)) $std->COD_MUN = $codMunEmitente;
            }

            try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\Z0150($std) . "\r\n"; } catch (\Exception $e) {}
        }

        // LIMPEZA E CRIAÇÃO DO DICIONÁRIO DE UNIDADES (0190)
        $unidadesLimpias = [];
        foreach($unidadesUsadas as $un => $val){
            $unL = strtoupper(trim(preg_replace('/[^A-Z0-9]/', '', $un)));

            if ($unL == 'P') $unL = 'UN';

            if (!empty($unL) && !isset($unidadesLimpias[$unL])) {
                $unidadesLimpias[$unL] = true;
                $std = new \stdClass();
                $std->UNID = substr($unL, 0, 6);
                $std->DESCR = $this->getDescricaoUnidade($unL);
                try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\Z0190($std) . "\r\n"; } catch (\Exception $e) {}
            }
        }

        foreach($produtosUsados as $cod => $pInfo){
            $std = new \stdClass();
            $std->COD_ITEM = $cod;
            $std->DESCR_ITEM = $pInfo['descr'];
            $std->UNID_INV = $pInfo['unid'];
            $std->TIPO_ITEM = $pInfo['tipo'];
            $std->COD_NCM = $pInfo['ncm'];
            try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\Z0200($std) . "\r\n"; } catch (\Exception $e) {}

            if (isset($unidadesDiferentes[$cod])) {
                foreach($unidadesDiferentes[$cod] as $unXml => $val) {
                    $fatConv = number_format(1, 6, ',', '');
                    $sped .= "|0220|{$unXml}|{$fatConv}||\r\n";
                }
            }
        }
        $sped .= '|0990|' . $this->totalizeBloco($sped, '0') . "|\r\n";
        $sped .= "|B001|1|\r\n|B990|2|\r\n";

        // ==========================================
        // 5. BLOCO C (NOTAS FISCAIS)
        // ==========================================
        $stdC001 = new \stdClass(); $stdC001->IND_MOV = '0';
        try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\C001($stdC001) . "\r\n"; } catch (\Exception $e) {}

        $dataLimite = \Carbon\Carbon::parse($dataFinal);

        // --- GERAÇÃO BLOCO C - ARQUIVOS XML ---
        foreach($notasValidasXml as $l){
            $ide = $speedService->getIde($l['xml']);

            $infNFe = null;
            if (isset($l['xml']->NFe->infNFe)) {
                $infNFe = $l['xml']->NFe->infNFe;
            } elseif (isset($l['xml']->infNFe)) {
                $infNFe = $l['xml']->infNFe;
            }

            $totalXml = null;
            if ($infNFe && isset($infNFe->total->ICMSTot)) {
                $totalXml = $infNFe->total->ICMSTot;
            }

            $docPart = $l['obj']->doc_part_ext ?? preg_replace('/[^0-9]/', '', (string)($speedService->getDestinatario($l['xml'])->CNPJ ?? ''));
            $chaveXml = $speedService->getChave($l['xml']);

            $std = new \stdClass();
            $std->IND_OPER = (string)$ide->tpNF; $std->IND_EMIT = '0'; $std->COD_PART = $docPart;
            $std->COD_MOD = (string)$ide->mod;

            $codSit = ($ide->finNFe == '2') ? '06' : '00';
            if (strlen($chaveXml) == 44) {
                $cnpjDaChave = substr($chaveXml, 6, 14);
                $docLimpo = str_pad($docPart, 14, '0', STR_PAD_LEFT);
                $empresaPad = str_pad($cnpjEmpresa, 14, '0', STR_PAD_LEFT);
                if ($cnpjDaChave !== $docLimpo && $cnpjDaChave !== $empresaPad) {
                    $codSit = '08';
                }
            }
            $std->COD_SIT = $codSit;

            $std->SER = str_pad((string)$ide->serie, 3, "0", STR_PAD_LEFT); $std->NUM_DOC = (string)$ide->nNF;
            $std->CHV_NFE = $chaveXml;
            $std->DT_DOC = \Carbon\Carbon::parse(substr((string)$ide->dhEmi, 0, 10))->format('dmY');
            $std->DT_E_S = $std->DT_DOC;
            $std->VL_DOC = (float)($totalXml->vNF ?? 0);
            $std->IND_PGTO = '2';
            $std->VL_DESC = (float)($totalXml->vDesc ?? 0);
            $std->VL_MERC = (float)($totalXml->vProd ?? 0);
            $std->IND_FRT = '3';

            $std->VL_BC_ICMS = (float)($totalXml->vBC ?? 0);
            $std->VL_ICMS = (float)($totalXml->vICMS ?? 0);
            $std->VL_BC_ICMS_ST = (float)($totalXml->vBCST ?? 0);
            $std->VL_ICMS_ST = (float)($totalXml->vST ?? 0);
            $std->VL_IPI = (float)($totalXml->vIPI ?? 0);

            // AJUSTE PIS/COFINS: Se for operação de entrada (ex: produtor/sucata), zera PIS/COFINS no C100
            if ($std->IND_OPER == '0') {
                $std->VL_PIS = 0.00;
                $std->VL_COFINS = 0.00;
            } else {
                $std->VL_PIS = (float)($totalXml->vPIS ?? 0);
                $std->VL_COFINS = (float)($totalXml->vCOFINS ?? 0);
            }

            if($std->IND_OPER == '1') $somaICMS += $std->VL_ICMS;
            try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\C100($std) . "\r\n"; } catch (\Exception $e) {}

            $dataC190 = [];
            $itensXml = $speedService->getItemNfe($l['xml']);
            foreach($itensXml as $itemXml){
                $cfopItem = (string)$itemXml->prod->CFOP;
                if($cfopItem == '2404') $cfopItem = '2403';
                if(in_array($cfopItem, ['1933', '2933'])) continue;
                if (isset($regras1400[$cfopItem])) {
                    $codIpm = $regras1400[$cfopItem];
                    $valorItem = (float)$itemXml->prod->vProd;
                    if (!isset($totais1400[$codMunEmitente][$codIpm])) { $totais1400[$codMunEmitente][$codIpm] = 0; }
                    $totais1400[$codMunEmitente][$codIpm] += $valorItem;
                }
                $dataC190 = $this->agruparC190Interno($dataC190, $this->prepararStdC190($itemXml));
            }
            foreach($dataC190 as $g) $sped .= new \NFePHP\EFD\Elements\ICMSIPI\C190($g) . "\r\n";
        }

        // --- GERAÇÃO C100 PARA NOTAS DO BANCO (TERCEIROS/MANUAIS) ---
        foreach($notasValidasBanco as $compra){
            $docForn = $compra->doc_part_ext ?? preg_replace('/[^0-9]/', '', $compra->fornecedor->cpf_cnpj ?? '');
            if (empty($docForn) || $docForn == '00000000000000') continue;

            $dtES = \Carbon\Carbon::parse($compra->created_at);
            if($dtES->gt($dataLimite)) $dtES = $dataLimite;

            $chaveLimpa = preg_replace('/[^0-9]/', '', (string)($compra->chave ?? ''));
            $serieChave = substr($chaveLimpa, 22, 3);
            $serieNota = (strlen($chaveLimpa) == 44 && is_numeric($serieChave)) ? (int)$serieChave : (int)($compra->serie ?? 1);

            $std = new \stdClass();
            $std->IND_OPER = '0';
            $std->IND_EMIT = ($compra->xml_importado == 0) ? '0' : '1';

            $std->COD_PART = $docForn;
            $std->COD_MOD = (strlen($chaveLimpa) == 44) ? '55' : '01';

            $std->COD_SIT = '00';
            if (strlen($chaveLimpa) == 44) {
                $cnpjDaChave = substr($chaveLimpa, 6, 14);
                $docLimpo = str_pad($docForn, 14, '0', STR_PAD_LEFT);
                $empresaPad = str_pad($cnpjEmpresa, 14, '0', STR_PAD_LEFT);
                if ($cnpjDaChave !== $docLimpo && $cnpjDaChave !== $empresaPad) {
                    $std->COD_SIT = '08';
                }
            }

            $std->SER = str_pad((string)$serieNota, 3, "0", STR_PAD_LEFT);
            $std->NUM_DOC = preg_replace('/[^0-9]/', '', (string)($compra->nf ?? '0'));
            $std->CHV_NFE = (strlen($chaveLimpa) == 44) ? $chaveLimpa : '';

            $std->DT_DOC = \Carbon\Carbon::parse($compra->data_emissao)->format('dmY');
            $std->DT_E_S = $dtES->format('dmY');
            $std->VL_DOC = (float)$compra->valor;
            $std->IND_PGTO = '2';
            $std->VL_DESC = (float)$compra->desconto;
            $std->VL_MERC = (float)$compra->somaItems();
            $std->IND_FRT = '9';

            $sumBcIcms = 0; $sumIcms = 0;
            foreach($compra->itens_para_sped as $item) {
                $sumBcIcms += (float)$item->vbc_icms;
                $sumIcms += (float)$item->v_icms;
            }
            $std->VL_BC_ICMS = (float)$compra->vbc_icms > 0 ? (float)$compra->vbc_icms : $sumBcIcms;
            $std->VL_ICMS = (float)$compra->v_icms > 0 ? (float)$compra->v_icms : $sumIcms;

            $std->VL_IPI = (float)$compra->v_ipi;

            // AJUSTE PIS/COFINS: Se for emissão própria de entrada ou não for nota de crédito, zera no cabeçalho
            if ($std->IND_EMIT == '0') {
                $std->VL_PIS = 0.00;
                $std->VL_COFINS = 0.00;
            } else {
                $std->VL_PIS = (float)$compra->v_pis;
                $std->VL_COFINS = (float)$compra->v_cofins;
            }

            $somaCreditos += $std->VL_ICMS;

            try {
                $sped .= new \NFePHP\EFD\Elements\ICMSIPI\C100($std) . "\r\n";
            } catch (\Exception $e) {
                $sped .= "|ERRO-C100-BANCO-NF-" . $std->NUM_DOC . "|" . $e->getMessage() . "|\r\n";
            }

            $dataC190 = [];

            // Rateio de Desconto por Item
            $descontoGlobal = $std->VL_DESC;
            $valorMercadoria = $std->VL_MERC;
            $descontoAcumuladoItens = 0;
            $totalItens = count($compra->itens_para_sped);

            foreach($compra->itens_para_sped as $idx => $item){
                $cfopItem = (string)$item->cfop_entrada;
                if($cfopItem == '2404') $cfopItem = '2403';
                $valorTotalItem = (float)$item->quantidade * $item->valor_unitario;

                // Cálculo Rateio
                $descontoItem = 0;
                if ($descontoGlobal > 0 && $valorMercadoria > 0) {
                    if ($idx == $totalItens - 1) {
                        $descontoItem = $descontoGlobal - $descontoAcumuladoItens;
                    } else {
                        $descontoItem = round(($valorTotalItem / $valorMercadoria) * $descontoGlobal, 2);
                        $descontoAcumuladoItens += $descontoItem;
                    }
                }

                if (isset($regras1400[$cfopItem])) {
                    $codIpm = $regras1400[$cfopItem];
                    if (!isset($totais1400[$codMunEmitente][$codIpm])) { $totais1400[$codMunEmitente][$codIpm] = 0; }
                    $totais1400[$codMunEmitente][$codIpm] += $valorTotalItem;
                }

                $std170 = new \stdClass();
                $std170->NUM_ITEM = $idx + 1;
                $std170->COD_ITEM = (string)$item->produto_id;
                $std170->DESCR_COMPL = strtoupper(trim((string)$item->produto->nome));
                $std170->QTD = (float)$item->quantidade;

                $unL = strtoupper(trim((string)($item->unidade_compra ?? 'UN')));
                $unL = preg_replace('/[^A-Z0-9]/', '', $unL);
                if (empty($unL) || $unL == 'P') $unL = 'UN';
                $std170->UNID = substr($unL, 0, 6);

                $std170->VL_ITEM = $valorTotalItem;
                $std170->VL_DESC = $descontoItem;
                $std170->IND_MOV = '0';
                $std170->CST_ICMS = str_pad($item->cst_icms ?? '000', 3, "0", STR_PAD_LEFT);
                $std170->CFOP = $cfopItem;
                $std170->VL_BC_ICMS = (float)$item->vbc_icms;
                $std170->ALIQ_ICMS = (float)$item->p_icms;
                $std170->VL_ICMS = (float)$item->v_icms;

                // AJUSTE PIS/COFINS ITEM (C170)
                $cstPisVal = (string)($item->cst_pis ?? '70');
                $std170->CST_PIS = $cstPisVal;
                if (in_array($cstPisVal, ['70', '71', '72', '73', '74', '75', '98', '99']) || $std->IND_EMIT == '0') {
                    $std170->VL_BC_PIS = 0.00;
                    $std170->ALIQ_PIS = 0.00;
                    $std170->VL_PIS = 0.00;
                } else {
                    $std170->VL_BC_PIS = (float)$item->vbc_pis;
                    $std170->ALIQ_PIS = (float)$item->p_pis;
                    $std170->VL_PIS = (float)$item->v_pis;
                }

                $cstCofinsVal = (string)($item->cst_cofins ?? '70');
                $std170->CST_COFINS = $cstCofinsVal;
                if (in_array($cstCofinsVal, ['70', '71', '72', '73', '74', '75', '98', '99']) || $std->IND_EMIT == '0') {
                    $std170->VL_BC_COFINS = 0.00;
                    $std170->ALIQ_COFINS = 0.00;
                    $std170->VL_COFINS = 0.00;
                } else {
                    $std170->VL_BC_COFINS = (float)$item->vbc_cofins;
                    $std170->ALIQ_COFINS = (float)$item->p_cofins;
                    $std170->VL_COFINS = (float)$item->v_cofins;
                }

                try {
                    $sped .= new \NFePHP\EFD\Elements\ICMSIPI\C170($std170) . "\r\n";
                } catch (\Exception $e) {
                    $sped .= "|ERRO-C170-BANCO-NF-" . $std->NUM_DOC . "|" . $e->getMessage() . "|\r\n";
                }

                $std190 = new \stdClass();
                $std190->CST_ICMS = $std170->CST_ICMS;
                $std190->CFOP = $std170->CFOP;
                $std190->ALIQ_ICMS = number_format((float)$std170->ALIQ_ICMS, 2, '.', '');
                $std190->VL_OPR = $std170->VL_ITEM;
                $std190->VL_BC_ICMS = $std170->VL_BC_ICMS;
                $std190->VL_ICMS = $std170->VL_ICMS;
                $std190->VL_BC_ICMS_ST = 0;
                $std190->VL_ICMS_ST = 0;
                $std190->VL_RED_BC = 0;
                $std190->VL_IPI = 0;

                $dataC190 = $this->agruparC190Interno($dataC190, $std190);
            }
            foreach($dataC190 as $g) {
                try {
                    $sped .= new \NFePHP\EFD\Elements\ICMSIPI\C190($g) . "\r\n";
                } catch (\Exception $e) {}
            }
        }
        $sped .= '|C990|' . $this->totalizeBloco($sped, 'C') . "|\r\n";

        // ==========================================
        // 6. BLOCO D (CT-e) - PRESTAÇÃO DE SERVIÇO
        // ==========================================
        $temCte = count($ctesValidosXml) > 0;
        $stdD001 = new \stdClass(); $stdD001->IND_MOV = $temCte ? '0' : '1';
        try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\D001($stdD001) . "\r\n"; } catch (\Exception $e) {}

        if ($temCte) {
            foreach ($ctesValidosXml as $infCte) {
                $cnpjTomador = '';
                if (isset($infCte->ide->toma3)) {
                    $toma = (string)$infCte->ide->toma3->toma;
                    if ($toma == '0') $cnpjTomador = (string)($infCte->rem->CNPJ ?? $infCte->rem->CPF ?? '');
                    elseif ($toma == '1') $cnpjTomador = (string)($infCte->exped->CNPJ ?? $infCte->exped->CPF ?? '');
                    elseif ($toma == '2') $cnpjTomador = (string)($infCte->receb->CNPJ ?? $infCte->receb->CPF ?? '');
                    elseif ($toma == '3') $cnpjTomador = (string)($infCte->dest->CNPJ ?? $infCte->dest->CPF ?? '');
                } elseif (isset($infCte->ide->toma4)) {
                    $cnpjTomador = (string)($infCte->ide->toma4->CNPJ ?? $infCte->ide->toma4->CPF ?? '');
                }

                $vPrest = (float)$infCte->vPrest->vTPrest; $cfopCte = (string)$infCte->ide->CFOP;
                $cst = '00'; $vBC = 0; $pICMS = 0; $vICMS = 0; $imp = $infCte->imp->ICMS;
                if (isset($imp->ICMS00)) { $cst = '00'; $vBC = (float)$imp->ICMS00->vBC; $pICMS = (float)$imp->ICMS00->pICMS; $vICMS = (float)$imp->ICMS00->vICMS; }
                elseif (isset($imp->ICMS20)) { $cst = '20'; $vBC = (float)$imp->ICMS20->vBC; $pICMS = (float)$imp->ICMS20->pICMS; $vICMS = (float)$imp->ICMS20->vICMS; }
                elseif (isset($imp->ICMS90)) { $cst = '90'; $vBC = (float)$imp->ICMS90->vBC; $pICMS = (float)$imp->ICMS90->pICMS; $vICMS = (float)$imp->ICMS90->vICMS; }

                $stdD100 = new \stdClass();
                $stdD100->IND_OPER = '1'; $stdD100->IND_EMIT = '0'; $stdD100->COD_PART = $cnpjTomador;
                $stdD100->COD_MOD = '57'; $stdD100->COD_SIT = '00'; $stdD100->SER = str_pad((string)$infCte->ide->serie, 3, "0", STR_PAD_LEFT);
                $stdD100->NUM_DOC = (string)$infCte->ide->nCT; $stdD100->CHV_CTE = (string)str_replace('CTe', '', $infCte['Id']);
                $stdD100->DT_DOC = \Carbon\Carbon::parse((string)substr($infCte->ide->dhEmi, 0, 10))->format('dmY');
                $stdD100->DT_A_P = $stdD100->DT_DOC; $stdD100->TP_CT_e = (string)$infCte->ide->tpCTe;
                $stdD100->VL_DOC = $vPrest; $stdD100->VL_DESC = 0; $stdD100->IND_FRT = '0'; $stdD100->VL_SERV = $vPrest;
                $stdD100->VL_BC_ICMS = $vBC; $stdD100->VL_ICMS = $vICMS;
                $stdD100->COD_MUN_ORIG = (string)$infCte->ide->cMunEnv; $stdD100->COD_MUN_DEST = (string)$infCte->ide->cMunFim;
                $somaICMS += $vICMS;
                try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\D100($stdD100) . "\r\n"; } catch (\Exception $e) {}

                $stdD190 = new \stdClass();
                $stdD190->CST_ICMS = str_pad($cst, 3, "0", STR_PAD_LEFT); $stdD190->CFOP = $cfopCte;
                $stdD190->ALIQ_ICMS = number_format($pICMS, 2, '.', ''); $stdD190->VL_OPR = $vPrest;
                $stdD190->VL_BC_ICMS = $vBC; $stdD190->VL_ICMS = $vICMS; $stdD190->VL_RED_BC = 0; $stdD190->COD_OBS = '';
                try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\D190($stdD190) . "\r\n"; } catch (\Exception $e) {}

                if (isset($regras1400[$cfopCte])) {
                    $codIpm = $regras1400[$cfopCte];
                    if (!isset($totais1400[$codMunEmitente][$codIpm])) { $totais1400[$codMunEmitente][$codIpm] = 0; }
                    $totais1400[$codMunEmitente][$codIpm] += $vPrest;
                }
            }
        }
        $sped .= '|D990|' . $this->totalizeBloco($sped, 'D') . "|\r\n";


        // ======= INÍCIO: MATEMÁTICA DO BLOCO E (ICMS NORMAL + DIFAL) =======
        $valorApurado = $somaICMS - $somaCreditos;

        $stdE110 = new \stdClass();
        $stdE110->VL_TOT_DEBITOS = $somaICMS;
        $stdE110->VL_AJ_DEBITOS = 0;
        $stdE110->VL_TOT_AJ_DEBITOS = 0;
        $stdE110->VL_ESTORNOS_CRED = 0;
        $stdE110->VL_TOT_CREDITOS = $somaCreditos;
        $stdE110->VL_AJ_CREDITOS = 0;
        $stdE110->VL_TOT_AJ_CREDITOS = 0;
        $stdE110->VL_ESTORNOS_DEB = 0;
        $stdE110->VL_SLD_CREDOR_ANT = 0;

        if ($valorApurado > 0) {
            $stdE110->VL_SLD_APURADO = $valorApurado;
            $stdE110->VL_SLD_CREDOR_TRANSPORTAR = 0;
        } else {
            $stdE110->VL_SLD_APURADO = 0;
            $stdE110->VL_SLD_CREDOR_TRANSPORTAR = abs($valorApurado);
        }

        $stdE110->VL_TOT_DED = 0;
        $stdE110->DEB_ESP = $totalDifal;

        $stdE110->VL_ICMS_RECOLHER = $stdE110->VL_SLD_APURADO;
        // ======= FIM: MATEMÁTICA DO BLOCO E =======


        // ======= INÍCIO: GERAÇÃO FÍSICA DO BLOCO E =======
        try {
            $stdE001 = new \stdClass();
            $stdE001->IND_MOV = 0;
            $sped .= new \NFePHP\EFD\Elements\ICMSIPI\E001($stdE001) . "\r\n";

            $stdE100 = new \stdClass();
            $stdE100->DT_INI = \Carbon\Carbon::parse($dataInicial)->format('dmY');
            $stdE100->DT_FIN = \Carbon\Carbon::parse($dataFinal)->format('dmY');
            $sped .= new \NFePHP\EFD\Elements\ICMSIPI\E100($stdE100) . "\r\n";
        } catch (\Exception $e) {
            $sped .= "|ERRO-E001-E100|" . $e->getMessage() . "|\r\n";
        }

        try {
            $sped .= new \NFePHP\EFD\Elements\ICMSIPI\E110($stdE110) . "\r\n";
        } catch (\Exception $e) {
            $sped .= "|ERRO-E110|" . $e->getMessage() . "|\r\n";
        }

        // Lógica do DIFAL (Blocos E111 e E113 intactos)
        if ($totalDifal > 0) {
            $stdE111 = new \stdClass();
            $stdE111->COD_AJ_APUR = 'BA050002';
            $stdE111->DESCR_COMPL_AJ = 'DIFAL Uso e Consumo e Ativo Imobilizado';
            $stdE111->VL_AJ_APUR = $totalDifal;

            try {
                $sped .= new \NFePHP\EFD\Elements\ICMSIPI\E111($stdE111) . "\r\n";
            } catch (\Exception $e) {
                $sped .= "|ERRO-E111|" . $e->getMessage() . "|\r\n";
            }

            foreach ($difalAgrupado as $itemDifal) {
                $chaveLimpa = preg_replace('/[^0-9]/', '', (string)($itemDifal->chave ?? ''));
                $serieNota = 1;
                if (strlen($chaveLimpa) == 44) {
                    $serieChave = substr($chaveLimpa, 22, 3);
                    if (is_numeric($serieChave)) {
                        $serieNota = (int)$serieChave;
                    }
                }

                $stdE113 = new \stdClass();
                $stdE113->COD_PART = preg_replace('/[^0-9]/', '', (string)($itemDifal->doc_fornecedor ?? ''));
                $stdE113->COD_MOD = '55';
                $stdE113->SER = str_pad((string)$serieNota, 3, "0", STR_PAD_LEFT);
                $stdE113->SUB = '';
                $stdE113->NUM_DOC = (string)$itemDifal->nf;
                $stdE113->DT_DOC = \Carbon\Carbon::parse($itemDifal->data_emissao)->format('dmY');
                $stdE113->COD_ITEM = '';
                $stdE113->VL_AJ_ITEM = (float) $itemDifal->valor_difal;
                $stdE113->CHV_DOCe = $chaveLimpa;

                try {
                    $sped .= new \NFePHP\EFD\Elements\ICMSIPI\E113($stdE113) . "\r\n";
                } catch (\Exception $e) {
                    $sped .= "|ERRO-E113-NOTA-" . $itemDifal->nf . "|" . $e->getMessage() . "|\r\n";
                }
            }
        }

        // GUIAS DE PAGAMENTO INDEPENDENTES
        $guiaNormal = $stdE110->VL_ICMS_RECOLHER;
        $guiaDifal = $totalDifal;

        if ($guiaNormal > 0) {
            $stdE116 = new \stdClass();
            $stdE116->COD_OR = '000';
            $stdE116->VL_OR = $guiaNormal;
            $stdE116->DT_VCTO = $dFinal;
            $stdE116->COD_REC = !empty($spedConfig->codigo_receita) ? $spedConfig->codigo_receita : '0775';
            $stdE116->MES_REF = $mesRef;
            $stdE116->TXT_COMPL = 'Recolhimento ICMS Normal Comércio';
            try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\E116($stdE116) . "\r\n"; } catch (\Exception $e) {}
        }

        if ($guiaDifal > 0) {
            $stdE116Difal = new \stdClass();
            $stdE116Difal->COD_OR = '000';
            $stdE116Difal->VL_OR = $guiaDifal;
            $stdE116Difal->DT_VCTO = $dFinal;
            $stdE116Difal->COD_REC = '2175';
            $stdE116Difal->MES_REF = $mesRef;
            $stdE116Difal->TXT_COMPL = 'Recolhimento DIFAL Uso e Consumo';
            try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\E116($stdE116Difal) . "\r\n"; } catch (\Exception $e) {}
        }

        $sped .= '|E990|' . $this->totalizeBloco($sped, 'E') . "|\r\n";
        $sped .= "|G001|1|\r\n|G990|2|\r\n";

        if($inventario == 0){
            $sped .= "|H001|1|\r\n|H990|2|\r\n";
        } else {
            $somaEstoque = $this->somaEstoque($filial_id);
            $std = new \stdClass(); $std->IND_MOV = 0;
            $sped .= new \NFePHP\EFD\Elements\ICMSIPI\H001($std) . "\r\n";
            $std = new \stdClass(); $std->DT_INV = \Carbon\Carbon::parse($dataInventario)->format('dmY');
            $std->VL_INV = number_format($somaEstoque, 2, '.', ''); $std->MOT_INV = $motivoInventario;
            $sped .= new \NFePHP\EFD\Elements\ICMSIPI\H005($std) . "\r\n";
            $itensDoEstoque = $this->getItensEstoque($filial_id);
            foreach($itensDoEstoque as $i){
                $std = new \stdClass(); $std->COD_ITEM = $i->produto->id;

                $unL = strtoupper(trim((string)$i->produto->unidade_venda));
                $unL = preg_replace('/[^A-Z0-9]/', '', $unL);
                if ($unL == 'P') $unL = 'UN';

                $std->UNID = substr($unL, 0, 6);
                $std->QTD = number_format($i->quantidade, 2, '.', ''); $std->VL_UNIT = number_format($i->valor_compra, 2, '.', '');
                $std->VL_ITEM = number_format($i->quantidade*$i->valor_compra, 2, '.', ''); $std->IND_PROP = 1; $std->COD_PART = $cnpjEmpresa;
                $std->TXT_COMPL = $i->produto->nome; $std->COD_CTA = $spedConfig->codigo_conta_analitica ?? '';
                $sped .= new \NFePHP\EFD\Elements\ICMSIPI\H010($std) . "\r\n";
            }
            $sped .= '|H990|' . $this->totalizeBloco($sped, 'H') . "|\r\n";
        }

        $sped .= "|K001|1|\r\n|K990|2|\r\n";
        $sped .= "|1001|0|\r\n";
        $tem1400 = (count($totais1400) > 0) ? 'S' : 'N';
        $sped .= "|1010|N|N|N|N|{$tem1400}|N|N|N|N|N|N|N|N|\r\n";
        foreach ($totais1400 as $codMun => $ipms) {
            foreach ($ipms as $codIpm => $valorTotal) {
                $valorFormatado = number_format($valorTotal, 2, ',', '');
                $sped .= "|1400|{$codIpm}|{$codMun}|{$valorFormatado}|\r\n";
            }
        }
        $sped .= '|1990|' . $this->totalizeBloco($sped, '1') . "|\r\n";
        $sped .= $this->totalize($sped);

        $path = public_path("sped_files/")."SPED-EFD-" . $cnpjEmpresa . ".txt";
        file_put_contents($path, $sped);
        return response()->download($path);
    }

    private function getDescricaoUnidade($sigla) {
        $map = [
            'UN' => 'UNIDADE', 'UNID' => 'UNIDADE', 'UNI' => 'UNIDADE', 'U' => 'UNIDADE',
            'L' => 'LITRO', 'LT' => 'LITRO', 'LTS' => 'LITROS',
            'KG' => 'QUILOGRAMA', 'QUILO' => 'QUILOGRAMA', 'KGS' => 'QUILOGRAMAS',
            'PC' => 'PECA', 'PÇ' => 'PECA', 'PCS' => 'PECAS',
            'CX' => 'CAIXA', 'CJ' => 'CONJUNTO',
            'JG' => 'JOGO', 'JGS' => 'JOGOS',
            'M' => 'METRO', 'MT' => 'METRO', 'MTS' => 'METROS',
            'M2' => 'METRO QUADRADO', 'M3' => 'METRO CUBICO',
            'PR' => 'PAR', 'PAR' => 'PAR', 'PARES' => 'PARES',
            'PCT' => 'PACOTE', 'PT' => 'PACOTE', 'PACOTE' => 'PACOTE',
            'RL' => 'ROLO', 'ROL' => 'ROLO',
            'FD' => 'FARDO', 'FDO' => 'FARDO',
            'KIT' => 'KIT', 'KT' => 'KIT',
            'BD' => 'BALDE', 'BLD' => 'BALDE', 'BL' => 'BALDE',
            'ML' => 'MILILITRO',
            'MI' => 'MILHEIRO', 'MIL' => 'MILHEIRO',
            'BB' => 'BOMBONA', 'BOMB' => 'BOMBONA',
            'TON' => 'TONELADA', 'TN' => 'TONELADA',
            'G' => 'GRAMA', 'GR' => 'GRAMA',
            'GL' => 'GALAO',
            'DZ' => 'DUZIA',
            'SC' => 'SACO'
        ];
        $key = strtoupper(trim($sigla));
        return $map[$key] ?? 'UNIDADE ' . $key;
    }

    private function totalizeBloco($sped, $bloco) {
        $linhas = explode("\r\n", $sped); $cont = 0;
        foreach ($linhas as $linha) {
            if (trim($linha) != "") {
                $cols = explode("|", $linha);
                if (isset($cols[1]) && substr($cols[1], 0, 1) == $bloco) { $cont++; }
            }
        }
        return $cont + 1;
    }

    private function somaEstoque($filial_id) {
        return \App\Models\Estoque::where('empresa_id', $this->empresa_id)
            ->when(empty($filial_id), function($q) { return $q->whereNull('filial_id'); })
            ->when(!empty($filial_id), function($q) use ($filial_id) { return $q->where('filial_id', $filial_id); })
            ->where('quantidade', '>', 0)
            ->select(\DB::raw('sum(quantidade * valor_compra) as total'))
            ->first()->total ?? 0;
    }

    private function getItensEstoque($filial_id) {
        return \App\Models\Estoque::where('empresa_id', $this->empresa_id)
            ->when(empty($filial_id), function($q) { return $q->whereNull('filial_id'); })
            ->when(!empty($filial_id), function($q) use ($filial_id) { return $q->where('filial_id', $filial_id); })
            ->where('quantidade', '>', 0)->with('produto')->get();
    }

    function totalize($efd) {
        $tot = ''; $keys = []; $aefd = explode("\n", $efd);
        foreach ($aefd as $element) {
            $param = explode("|", $element);
            if (!empty($param[1])) {
                $key = $param[1];
                if (!empty($keys[$key])) { $keys[$key] += 1; } else { $keys[$key] = 1; }
            }
        }
        $tot .= "|9001|0|\n"; $n = 0;
        foreach ($keys as $key => $value) {
            if (!empty($key)) { $tot .= "|9900|$key|$value|\n"; $n++; }
        }
        $n++; $tot .= "|9900|9001|1|\n"; $tot .= "|9900|9900|" . ($n + 3) . "|\n";
        $tot .= "|9900|9990|1|\n"; $tot .= "|9900|9999|1|\n";
        $tot .= "|9990|" . ($n + 6) . "|\n"; $efd .= $tot;
        $n = count(explode("\n", $efd)); $tot .= "|9999|$n|\n";
        return $tot;
    }

    private function agruparC190Interno($dataC190, $std) {
        $chave = $std->CST_ICMS . $std->CFOP . $std->ALIQ_ICMS;
        if (isset($dataC190[$chave])) {
            $dataC190[$chave]->VL_OPR += $std->VL_OPR ?? 0;
            $dataC190[$chave]->VL_BC_ICMS += $std->VL_BC_ICMS ?? 0;
            $dataC190[$chave]->VL_ICMS += $std->VL_ICMS ?? 0;
            $dataC190[$chave]->VL_BC_ICMS_ST += $std->VL_BC_ICMS_ST ?? 0;
            $dataC190[$chave]->VL_ICMS_ST += $std->VL_ICMS_ST ?? 0;
            $dataC190[$chave]->VL_RED_BC += $std->VL_RED_BC ?? 0;
            $dataC190[$chave]->VL_IPI += $std->VL_IPI ?? 0;
        } else { $dataC190[$chave] = $std; }
        return $dataC190;
    }

    private function prepararStdC190($item) {
        $prod = $item->prod; $imposto = $item->imposto;

        $vBC = 0; $vICMS = 0; $pICMS = 0; $vBCST = 0; $vICMSST = 0; $pRedBC = 0;
        $cst_csosn = '90'; $origem = '0';

        if ($imposto && isset($imposto->ICMS)) {
            $icmsNodes = (array)$imposto->ICMS;
            foreach($icmsNodes as $node) {
                if (is_object($node)) {
                    $vBC = (float)($node->vBC ?? 0);
                    $vBCST = (float)($node->vBCST ?? 0);
                    $vICMSST = (float)($node->vICMSST ?? 0);
                    $vICMS = (float)($node->vICMS ?? 0);
                    $pICMS = (float)($node->pICMS ?? 0);
                    $pRedBC = (float)($node->pRedBC ?? 0);
                    $cst_csosn = (string)($node->CST ?? $node->CSOSN ?? '90');
                    $origem = (string)($node->orig ?? '0');
                    break;
                }
            }
        }

        if (strlen($cst_csosn) == 2) { $cst_csosn = $origem . $cst_csosn; } else { $cst_csosn = str_pad($cst_csosn, 3, "0", STR_PAD_LEFT); }

        $vIPI = 0;
        if ($imposto && isset($imposto->IPI)) {
            $arrIpi = array_values((array)$imposto->IPI);
            if (isset($arrIpi[0]) && is_object($arrIpi[0])) { $vIPI = (float)($arrIpi[0]->IPITrib->vIPI ?? 0); }
        }

        $cfopItem = (string)$prod->CFOP;
        if($cfopItem == '2404') $cfopItem = '2403';

        $std = new \stdClass();
        $std->CST_ICMS = $cst_csosn;
        $std->CFOP = $cfopItem;
        $std->ALIQ_ICMS = number_format($pICMS, 2, '.', '');
        $std->VL_OPR = (float)$prod->vProd;
        $std->VL_BC_ICMS = $vBC;
        $std->VL_ICMS = $vICMS;
        $std->VL_BC_ICMS_ST = $vBCST;
        $std->VL_ICMS_ST = $vICMSST;
        $std->VL_RED_BC = $pRedBC;
        $std->VL_IPI = $vIPI;
        $std->COD_OBS = '';

        return $std;
    }
}
