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

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);
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
        $std->CNPJ = $cnpj;
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
            ->whereIn('estado', ['APROVADO', 'aprovado', '1'])
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
        
        // FUNÇÃO AUXILIAR PARA NOTAS DE EXPORTAÇÃO (CNPJ ZERADO)
        $extractDocPart = function($dest, $idFallback) {
            if (!$dest) return null;
            $doc = preg_replace('/[^0-9]/', '', (string)($dest->CNPJ ?? $dest->CPF ?? ''));
            if (empty($doc) || $doc == '00000000000000') {
                $idEstrangeiro = (string)($dest->idEstrangeiro ?? '');
                if (!empty($idEstrangeiro)) {
                    return 'EX' . preg_replace('/[^A-Za-z0-9]/', '', $idEstrangeiro);
                }
                return 'EX' . $idFallback; // Gera um código genérico para permitir a inclusão no SPED
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
                        // Ignora 2404 temporariamente caso tenha sido digitado errado na NF
                        if($cfop == '2404') $cfop = '2403'; 
                        if(!in_array($cfop, ['1933', '2933'])){ 
                            $temProdutoValido = true;
                        }
                    }
                    if($temProdutoValido){
                        $dest = $speedService->getDestinatario($xml);
                        $doc = $extractDocPart($dest, $v->id ?? rand(1000,9999));
                        
                        if($doc) {
                            $participantesUsados[$doc] = $dest;
                            $v->doc_part_ext = $doc; // Salva para o C100
                        }
                        
                        $notasValidasXml[] = ['xml' => $xml, 'tipo' => $source['tipo'], 'obj' => $v];
                    }
                } 
                else if ($source['tipo'] == 'compra') { // FALLBACK BANCO PARA COMPRA SEM XML
                    $itensFiltrados = [];
                    if (isset($v->itens)) {
                        foreach($v->itens as $item){
                            $cfop = (string)($item->cfop_entrada ?? '1102');
                            if(!in_array($cfop, ['1933', '2933'])){ 
                                $item->cfop_entrada = $cfop;
                                $itensFiltrados[] = $item;
                                $pDb = $item->produto;
                                if ($pDb) {
                                    $produtosUsados[(string)$item->produto_id] = [
                                        'descr' => (string)$pDb->nome,
                                        'unid'  => strtoupper(trim($item->unidade_compra)),
                                        'ncm'   => preg_replace('/[^0-9]/', '', $pDb->NCM ?? ''), 
                                        'tipo'  => str_pad($pDb->tipo_item ?? '00', 2, "0", STR_PAD_LEFT)
                                    ];
                                }
                                $unidadesUsadas[strtoupper(trim($item->unidade_compra))] = true;
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
        
        // --- FILTRAR COMPRAS DE TERCEIROS ---
        foreach($comprasImportadas as $compra){
            $itensFiltrados = [];
            foreach($compra->itens as $item){
                $cfop = (string)($item->cfop_entrada ?? '1102'); 
                if(!in_array($cfop, ['1933', '2933'])){ 
                    $item->cfop_entrada = $cfop; 
                    $itensFiltrados[] = $item;
                    $pDb = $item->produto;
                    if ($pDb) {
                        $produtosUsados[(string)$item->produto_id] = [
                            'descr' => (string)$pDb->nome,
                            'unid'  => strtoupper(trim($item->unidade_compra)),
                            'ncm'   => preg_replace('/[^0-9]/', '', $pDb->NCM ?? ''), 
                            'tipo'  => str_pad($pDb->tipo_item ?? '00', 2, "0", STR_PAD_LEFT)
                        ];
                    }
                    $unidadesUsadas[strtoupper(trim($item->unidade_compra))] = true;
                }
            }
            if(count($itensFiltrados) > 0){
                $compra->itens_para_sped = $itensFiltrados; 
                $notasValidasBanco[] = $compra;
                $docForn = preg_replace('/[^0-9]/', '', $compra->fornecedor->cpf_cnpj ?? '');
                if($docForn && $docForn != '00000000000000') $participantesUsados[$docForn] = $compra->fornecedor; 
            }
        }

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

            // Tratamento para Exterior / Exportação
            if (str_starts_with($doc, 'EX')) {
                $std->COD_PAIS = (string)($p->enderDest->cPais ?? '999');
                if(empty($std->COD_PAIS) || $std->COD_PAIS == '1058') $std->COD_PAIS = '999';
                $std->CNPJ = ''; 
                $std->CPF = '';
                $std->COD_MUN = '9999999';
            } else {
                $std->COD_PAIS = '1058';
                if(strlen($doc) == 11) { $std->CPF = $doc; $std->CNPJ = ''; } else { $std->CNPJ = $doc; $std->CPF = ''; }
                $std->COD_MUN = (string)($p->enderDest->cMun ?? $p->cidade->codigo ?? '');
            }

            try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\Z0150($std) . "\r\n"; } catch (\Exception $e) {}
        }

        foreach($unidadesUsadas as $un => $val){
            $std = new \stdClass(); $std->UNID = $un; $std->DESCR = "UNIDADE $un";
            $sped .= new \NFePHP\EFD\Elements\ICMSIPI\Z0190($std) . "\r\n";
        }
        
        foreach($produtosUsados as $cod => $pInfo){
            $std = new \stdClass();
            $std->COD_ITEM = $cod; $std->DESCR_ITEM = $pInfo['descr']; $std->UNID_INV = $pInfo['unid'];
            $std->TIPO_ITEM = $pInfo['tipo']; $std->COD_NCM = $pInfo['ncm'];
            try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\Z0200($std) . "\r\n"; } catch (\Exception $e) {}
        }
        $sped .= '|0990|' . $this->totalizeBloco($sped, '0') . "|\r\n";
        $sped .= "|B001|1|\r\n|B990|2|\r\n";

        // ==========================================
        // 5. BLOCO C (NOTAS FISCAIS)
        // ==========================================
        $stdC001 = new \stdClass(); $stdC001->IND_MOV = '0';
        try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\C001($stdC001) . "\r\n"; } catch (\Exception $e) {}

        $dataLimite = \Carbon\Carbon::parse($dataFinal);

        foreach($notasValidasXml as $l){
            $ide = $speedService->getIde($l['xml']);
            $totalXml = $l['xml']->infNFe->total->ICMSTot; // LEITURA DIRETA DO XML PARA GARANTIR ICMS
            
            // Pega o documento extraído pelo Helper, ou tenta recuperar
            $docPart = $l['obj']->doc_part_ext ?? preg_replace('/[^0-9]/', '', (string)($speedService->getDestinatario($l['xml'])->CNPJ ?? ''));

            $std = new \stdClass();
            $std->IND_OPER = (string)$ide->tpNF; $std->IND_EMIT = '0'; $std->COD_PART = $docPart; 
            $std->COD_MOD = (string)$ide->mod; $std->COD_SIT = ($ide->finNFe == '2') ? '06' : '00';
            $std->SER = str_pad((string)$ide->serie, 3, "0", STR_PAD_LEFT); $std->NUM_DOC = (string)$ide->nNF;
            $std->CHV_NFE = $speedService->getChave($l['xml']);
            $std->DT_DOC = \Carbon\Carbon::parse(substr((string)$ide->dhEmi, 0, 10))->format('dmY');
            $std->DT_E_S = $std->DT_DOC; 
            $std->VL_DOC = (float)($totalXml->vNF ?? 0);
            $std->IND_PGTO = '2'; 
            $std->VL_DESC = (float)($totalXml->vDesc ?? 0); 
            $std->VL_MERC = (float)($totalXml->vProd ?? 0);
            $std->IND_FRT = '3'; 
            
            // PREENCHIMENTO ESTRITO DE ICMS NO C100 BASEADO NO XML
            $std->VL_BC_ICMS = (float)($totalXml->vBC ?? 0); 
            $std->VL_ICMS = (float)($totalXml->vICMS ?? 0);
            $std->VL_BC_ICMS_ST = (float)($totalXml->vBCST ?? 0); 
            $std->VL_ICMS_ST = (float)($totalXml->vST ?? 0);
            $std->VL_IPI = (float)($totalXml->vIPI ?? 0); 
            $std->VL_PIS = (float)($totalXml->vPIS ?? 0); 
            $std->VL_COFINS = (float)($totalXml->vCOFINS ?? 0);

            if($std->IND_OPER == '1') $somaICMS += $std->VL_ICMS; 
            try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\C100($std) . "\r\n"; } catch (\Exception $e) {}

            $dataC190 = [];
            $itensXml = $speedService->getItemNfe($l['xml']);
            foreach($itensXml as $itemXml){
                $cfopItem = (string)$itemXml->prod->CFOP;
                if($cfopItem == '2404') $cfopItem = '2403'; // Ajuste temporário de erro de emissão
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

        foreach($notasValidasBanco as $compra){
            $docForn = $compra->doc_part_ext ?? preg_replace('/[^0-9]/', '', $compra->fornecedor->cpf_cnpj ?? '');
            if (empty($docForn) || $docForn == '00000000000000') continue;
            
            $dtES = \Carbon\Carbon::parse($compra->created_at); if($dtES->gt($dataLimite)) $dtES = $dataLimite;
            $chaveLimpa = preg_replace('/[^0-9]/', '', $compra->chave ?? '');
            $serieChave = substr($chaveLimpa, 22, 3);
            $serieNota = (strlen($chaveLimpa) == 44 && is_numeric($serieChave)) ? (int)$serieChave : (int)($compra->serie ?? 1);

            $std = new \stdClass();
            $std->IND_OPER = '0'; 
            $std->IND_EMIT = ($compra->xml_importado == 0) ? '0' : '1'; 
            $std->COD_PART = $docForn; 
            $std->COD_MOD = '55'; $std->COD_SIT = '00'; $std->SER = str_pad($serieNota, 3, "0", STR_PAD_LEFT); 
            $std->NUM_DOC = $compra->nf; $std->CHV_NFE = $compra->chave;
            $std->DT_DOC = \Carbon\Carbon::parse($compra->data_emissao)->format('dmY');
            $std->DT_E_S = $dtES->format('dmY'); $std->VL_DOC = (float)$compra->valor;
            $std->IND_PGTO = '2'; $std->VL_DESC = (float)$compra->desconto; $std->VL_MERC = (float)$compra->somaItems();
            $std->IND_FRT = '9'; 
            
            // SOMA AUTOMÁTICA DE ITENS PARA PREVENIR ERRO DO SPED QUANDO CAPA ESTÁ ZERADA NO BANCO
            $sumBcIcms = 0; $sumIcms = 0;
            foreach($compra->itens_para_sped as $item) {
                $sumBcIcms += (float)$item->vbc_icms;
                $sumIcms += (float)$item->v_icms;
            }
            $std->VL_BC_ICMS = (float)$compra->vbc_icms > 0 ? (float)$compra->vbc_icms : $sumBcIcms;
            $std->VL_ICMS = (float)$compra->v_icms > 0 ? (float)$compra->v_icms : $sumIcms;
            
            $std->VL_IPI = (float)$compra->v_ipi; $std->VL_PIS = (float)$compra->v_pis; $std->VL_COFINS = (float)$compra->v_cofins;

            $somaCreditos += $std->VL_ICMS; 
            try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\C100($std) . "\r\n"; } catch (\Exception $e) {}

            $dataC190 = [];
            foreach($compra->itens_para_sped as $idx => $item){
                $cfopItem = (string)$item->cfop_entrada;
                if($cfopItem == '2404') $cfopItem = '2403'; 
                $valorTotalItem = (float)$item->quantidade * $item->valor_unitario;
                if (isset($regras1400[$cfopItem])) {
                    $codIpm = $regras1400[$cfopItem];
                    if (!isset($totais1400[$codMunEmitente][$codIpm])) { $totais1400[$codMunEmitente][$codIpm] = 0; }
                    $totais1400[$codMunEmitente][$codIpm] += $valorTotalItem;
                }
                $std170 = new \stdClass(); $std170->NUM_ITEM = $idx + 1; $std170->COD_ITEM = (string)$item->produto_id;
                $std170->DESCR_COMPL = strtoupper(trim((string)$item->produto->nome)); $std170->QTD = (float)$item->quantidade;
                $std170->UNID = $produtosUsados[(string)$item->produto_id]['unid'] ?? strtoupper(substr($item->unidade_compra, 0, 6)); 
                $std170->VL_ITEM = $valorTotalItem; $std170->VL_DESC = 0; $std170->IND_MOV = '0';
                $std170->CST_ICMS = str_pad($item->cst_icms ?? '000', 3, "0", STR_PAD_LEFT); $std170->CFOP = $cfopItem;
                $std170->VL_BC_ICMS = (float)$item->vbc_icms; $std170->ALIQ_ICMS = (float)$item->p_icms; $std170->VL_ICMS = (float)$item->v_icms;
                $std170->CST_PIS = (string)$item->cst_pis ?: '70'; $std170->VL_BC_PIS = (float)$item->vbc_pis; $std170->ALIQ_PIS = (float)$item->p_pis; $std170->VL_PIS = (float)$item->v_pis;
                $std170->CST_COFINS = (string)$item->cst_cofins ?: '70'; $std170->VL_BC_COFINS = (float)$item->vbc_cofins; $std170->ALIQ_COFINS = (float)$item->p_cofins; $std170->VL_COFINS = (float)$item->v_cofins;
                try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\C170($std170) . "\r\n"; } catch (\Exception $e) {}

                $std190 = new \stdClass();
                $std190->CST_ICMS = $std170->CST_ICMS; $std190->CFOP = $std170->CFOP; $std190->ALIQ_ICMS = number_format($std170->ALIQ_ICMS, 2, '.', '');
                $std190->VL_OPR = $std170->VL_ITEM; $std190->VL_BC_ICMS = $std170->VL_BC_ICMS; $std190->VL_ICMS = $std170->VL_ICMS;
                $std190->VL_BC_ICMS_ST = 0; $std190->VL_ICMS_ST = 0; $std190->VL_RED_BC = 0; $std190->VL_IPI = 0;
                $dataC190 = $this->agruparC190Interno($dataC190, $std190);
            }
            foreach($dataC190 as $g) $sped .= new \NFePHP\EFD\Elements\ICMSIPI\C190($g) . "\r\n";
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

        // ==========================================
        // APURAÇÃO E DEMAIS BLOCOS (E, G, H, K)
        // ==========================================
        $std = new \stdClass(); $std->IND_MOV = '0';
        try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\E001($std) . "\r\n"; } catch (\Exception $e) {}
        $std = new \stdClass(); $std->DT_INI = $dInicial; $std->DT_FIN = $dFinal;
        try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\E100($std) . "\r\n"; } catch (\Exception $e) {}

        $valorApurado = $somaICMS - $somaCreditos;
        $stdE110 = new \stdClass();
        $stdE110->VL_TOT_DEBITOS = $somaICMS; $stdE110->VL_AJ_DEBITOS = 0; $stdE110->VL_TOT_AJ_DEBITOS = 0; $stdE110->VL_ESTORNOS_CRED = 0;
        $stdE110->VL_TOT_CREDITOS = $somaCreditos; $stdE110->VL_AJ_CREDITOS = 0; $stdE110->VL_TOT_AJ_CREDITOS = 0; $stdE110->VL_ESTORNOS_DEB = 0;
        $stdE110->VL_SLD_CREDOR_ANT = 0; 
        if ($valorApurado > 0) {
            $stdE110->VL_SLD_APURADO = $valorApurado; $stdE110->VL_ICMS_RECOLHER = $valorApurado; $stdE110->VL_SLD_CREDOR_TRANSPORTAR = 0;
        } else {
            $stdE110->VL_SLD_APURADO = 0; $stdE110->VL_ICMS_RECOLHER = 0; $stdE110->VL_SLD_CREDOR_TRANSPORTAR = abs($valorApurado);
        }
        $stdE110->VL_TOT_DED = 0; $stdE110->DEB_ESP = 0;
        try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\E110($stdE110) . "\r\n"; } catch (\Exception $e) {}

        if ($stdE110->VL_ICMS_RECOLHER > 0) {
            $stdE116 = new \stdClass(); $stdE116->COD_OR = '000'; $stdE116->VL_OR = $stdE110->VL_ICMS_RECOLHER; 
            $stdE116->DT_VCTO = $dFinal; $stdE116->COD_REC = $spedConfig ? $spedConfig->codigo_receita : ''; $stdE116->MES_REF = $mesRef;
            try { $sped .= new \NFePHP\EFD\Elements\ICMSIPI\E116($stdE116) . "\r\n"; } catch (\Exception $e) {}
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
                $std = new \stdClass(); $std->COD_ITEM = $i->produto->id; $std->UNID = $i->produto->unidade_venda;
                $std->QTD = number_format($i->quantidade, 2, '.', ''); $std->VL_UNIT = number_format($i->valor_compra, 2, '.', '');
                $std->VL_ITEM = number_format($i->quantidade*$i->valor_compra, 2, '.', ''); $std->IND_PROP = 1; $std->COD_PART = $cnpj;
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

        $path = public_path("sped_files/")."SPED-EFD-" . $cnpj . ".txt";
        file_put_contents($path, $sped);
        return response()->download($path);
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
        if($cfopItem == '2404') $cfopItem = '2403'; // Proteção contra CFOP inexistente vindo do emissor

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