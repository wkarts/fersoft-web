<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Filial;
use App\Models\Compra;
//use App\Models\ItemPurchase;
use App\Helpers\StockMove;
use App\Services\NFeEntradaService;
use App\Models\ConfigNota;
use App\Models\NaturezaOperacao;
//use NFePHP\DA\NFe\Danfe;
use App\Services\CustomDanfe as Danfe;
use App\Models\ItemCompra;
use App\Models\Produto;
use App\Models\Cidade;
use App\Models\Etiqueta;
use App\Models\CompraReferencia;
use NFePHP\DA\NFe\Daevento;
use Mail;
use App\Models\EscritorioContabil;
use Dompdf\Dompdf;
use App\Prints\CompraPrint80;
use App\Prints\PedidoCompraPrint80;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Fiscal\TransmissaoResult;
use NFePHP\NFe\Common\Standardize;

class PurchaseController extends Controller
{
    protected $empresa_id = null;
    protected $usuario_id = null;
    protected $filial_id  = null;

    public function __construct(){
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;

            $this->usuario_id = session('user_logged')['id'] ?? null;
            if (!$this->usuario_id) {
                return redirect('/login');
            }

            $this->filial_id = $request->get('filial_id')
                ?? session('user_logged.local_padrao')
                ?? null;

            return $next($request);
        });
    }

    public function numeroSequencial(){
        $verify = Compra::where('empresa_id', $this->empresa_id)
        ->where('numero_sequencial', 0)
        ->first();
        if($verify){
            $vendas = Compra::where('empresa_id', $this->empresa_id)
            ->get();

            $n = 1;
            foreach($vendas as $v){
                $v->numero_sequencial = $n;
                $n++;
                $v->save();
            }
        }
    }

    public function index(){

        $this->numeroSequencial();
        $totalRegistros = count(Compra::where('empresa_id', $this->empresa_id)->get());
        $compras = Compra::
        orderBy('id', 'desc')
        ->where('empresa_id', $this->empresa_id)
        ->paginate(15);

        $somaCompraMensal = $this->somaCompraMensal();
        return view('compraManual/listAll')
        ->with('compras', $compras)
        ->with('somaCompraMensal', $somaCompraMensal)
        ->with('links', true)
        ->with('graficoJs', true)
        ->with('title', 'Compras');

    }

    public function pesquisa(Request $request){
        $compras = Compra::pesquisaProduto($request->pesquisa);
        $totalRegistros = count($compras);

        $somaCompraMensal = $this->somaCompraMensal();
        return view('compraManual/listAll')
        ->with('compras', $compras)
        ->with('somaCompraMensal', $somaCompraMensal)
        ->with('graficoJs', true)
        ->with('title', 'Pequisa de Produto em Compras');

    }

    private function somaCompraMensal(){
        $compras = Compra::
        where('empresa_id', $this->empresa_id)
        ->get();
        $temp = [];
        $soma = 0;
        $mesAnterior = null;
        $anoAnterior = null;

        foreach($compras as $key => $c){
            $date = $c->created_at;
            $mes = substr($date, 5, 2);
            $ano = substr($date, 0, 4);


            if($mesAnterior != $mes){
                $temp["Mes: ".$mes."/$ano"] = $c->valor;
            }else{
                $temp["Mes: ".$mesAnterior."/$anoAnterior"] += $c->valor;

            }
            $mesAnterior = $mes;
            $anoAnterior = $ano;
        }

        return $temp;
    }

    private function somaCompraMensalFiltro($compras){
        $temp = [];
        $soma = 0;
        $mesAnterior = null;
        $anoAnterior = null;


        foreach($compras as $c){
            $date = $c->created_at;
            $mes = substr($date, 5, 2);
            $ano = substr($date, 0, 4);

            if($mesAnterior != $mes){
                $temp["Mes: ".$mes."/$ano"] = $c->valor;
            }else{
                $temp["Mes: ".$mesAnterior."/$anoAnterior"] += $c->valor;
            }
            $mesAnterior = $mes;
            $anoAnterior = $ano;
        }

        return $temp;
    }

    private function somaCompraDiarioFiltro($compras){
        $temp = [];
        $soma = 0;
        $diaAnterior = null;
        $mesAnterior = null;
        $s = 0;

        foreach($compras as $c){
            $date = $c->created_at;
            $dia = substr($date, 8, 2);
            $mes = substr($date, 5, 2);
            if($diaAnterior != $dia){
                $temp["Dia: ".$dia."/$mes"] = $c->valor;
            }else{
                $temp["Dia: ".$diaAnterior."/$mesAnterior"] += $c->valor;
                $s += $c->valor;
            }
            $mesAnterior = $mes;
            $diaAnterior = $dia;
        }

        return $temp;
    }
    private function diferencaEntreDatas($data1, $data2){
        $dif = strtotime($data2) - strtotime($data1);
        return floor($dif / (60 * 60 * 24));
    }

    public function filtro(Request $request){
        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $fornecedor = $request->fornecedor;
        $numero_nfe = $request->numero_nfe;
        $filial_id = $request->filial_id;
        $compras = null;
        $diferencaDatas = null;

        // if($dataInicial == null || $dataFinal == null || $fornecedor == null){
        //     session()->flash('mensagem_erro', 'Informe o fornecedor, data inicial e data final!');
        //     return redirect('/compras');
        // }
        $compras = Compra::
        select('compras.*')
        ->orderBy('compras.created_at', 'desc')
        ->where('compras.empresa_id', $this->empresa_id);

        if(($fornecedor)){
            $compras->join('fornecedors', 'fornecedors.id' , '=', 'compras.fornecedor_id')
            ->where('fornecedors.razao_social', 'LIKE', "%$fornecedor%");
        }
        if(($dataInicial) && isset($dataFinal)){
            $compras->whereBetween('compras.created_at', [
                $this->parseDate($dataInicial),
                $this->parseDate($dataFinal, true)
            ]);
        }
        if(($numero_nfe)){
            $compras->where('nf', 'LIKE', "%$numero_nfe%");
        }

        if($filial_id){
            if($filial_id == -1){
                $compras->where('filial_id', null);
            }else{
                $compras->where('filial_id', $filial_id);
            }
        }

        $compras = $compras->get();

        if(isset($dataInicial) && isset($dataFinal)){
            $diferencaDatas = $this->diferencaEntreDatas($this->parseDate($dataInicial), $this->parseDate($dataFinal));
        }

        if($diferencaDatas > 31 || $diferencaDatas == null){
            $somaCompraMensal = $this->somaCompraMensalFiltro($compras);
        }else{
            $somaCompraMensal = $this->somaCompraDiarioFiltro($compras);
        }

        return view('compraManual/listAll')
        ->with('compras', $compras)
        ->with('fornecedor', $fornecedor)
        ->with('dataInicial', $dataInicial)
        ->with('numero_nfe', $numero_nfe)
        ->with('dataFinal', $dataFinal)
        ->with('filial_id', $filial_id)
        ->with('somaCompraMensal', $somaCompraMensal)
        ->with('graficoJs', true)
        ->with('infoDados', "Contas filtradas")
        ->with('title', 'Filtro Compras');

    }

    private function parseDate($date, $plusDay = false){

        if($plusDay == false)
            return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
        else
            return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
    }

    public function downloadXml($id){
        $compra = Compra::
        where('id', $id)
        ->first();
        if(valida_objeto($compra)){
            //$public = env('SERVIDOR_WEB') ? 'public/' : '';
            $public = rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR;
            if($compra->nf > 0) return response()->download(public_path('xml_entrada/').$compra->chave. '.xml');
            else return response()->download(public_path('xml_entrada_emitida/').$compra->chave. '.xml');
        }else{
            return redirect('/403');
        }
    }

    public function downloadXmlCancela($id){
        $compra = Compra::
        where('id', $id)
        ->first();
        if(valida_objeto($compra)){
            //$public = env('SERVIDOR_WEB') ? 'public/' : '';
            $public = rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR;
            return response()->download(public_path('xml_nfe_entrada_cancelada/').$compra->chave. '.xml');
        }else{
            return redirect('/403');
        }
    }

    public function detalhes($id){
        $compra = Compra::
        where('id', $id)
        ->first();
        if(valida_objeto($compra)){
            $value = session('user_logged');

            return view('compraManual/detail')
            ->with('compra', $compra)
            ->with('adm', $value['adm'])
            ->with('title', 'Detalhes da compra');
        }else{
            return redirect('/403');
        }
    }

    public function delete($id){

        $compra = Compra::
        where('id', $id)
        ->first();
        if(valida_objeto($compra)){
            $stockMove = new StockMove();
            //$public = env('SERVIDOR_WEB') ? 'public/' : '';
            $public = rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR;

            if($compra->xml_path != "" && file_exists(public_path("xml_entrada/") . $compra->xml_path)){
                unlink(public_path("xml_entrada/") .$compra->xml_path);
            }
            foreach($compra->itens as $i){
        // baixa de estoque
                $stockMove->downStock($i->produto->id, $i->quantidade*$i->produto->conversao_unitaria, $compra->filial_id);
                $i->delete();
            }

            if($compra->delete()){
                session()->flash('mensagem_sucesso', 'Registro removido!');
            }else{
                session()->flash('mensagem_erro', 'Erro!');
            }
            return redirect('/compras');
        }else{
            return redirect('/403');
        }
    }

    public function itemCompra(Request $request){
        $item = ItemCompra::with('produto')->findOrFail($request->id);
        return response()->json($item, 200);
    }

    public function emitirEntrada($id){
        $compra = Compra::find($id);
        if(valida_objeto($compra)){
            $naturezas = NaturezaOperacao::
            where('empresa_id', $this->empresa_id)
            ->get();

            $cidades = Cidade::all();

            $dadosEntrada = true;
            $produtosInvalidos = [];
            foreach($compra->itens as $i){
                if(!$i->produto->CST_CSOSN_entrada || !$i->produto->CST_PIS_entrada || !$i->produto->CST_COFINS_entrada || !$i->produto->CST_IPI_entrada || !$i->produto->CFOP_entrada_estadual || !$i->produto->CFOP_entrada_inter_estadual){
                    $dadosEntrada = false;
                    array_push($produtosInvalidos, $i->produto_id);
                }
            }

            $tiposPagamento = Compra::tiposPagamento();
            return view('compraManual/emitirEntrada')
            ->with('compra', $compra)
            ->with('cidades', $cidades)
            ->with('naturezas', $naturezas)
            ->with('tiposPagamento', $tiposPagamento)
            ->with('dadosEntrada', $dadosEntrada)
            ->with('produtosInvalidos', $produtosInvalidos)
            ->with('NFeEntradaJS', true)
            ->with('title', 'Emitir NFe Entrada');
        }else{
            return redirect('/403');
        }
    }

    public function gerarEntrada(Request $request)
    {
        try {
            $compra = Compra::find($request->compra_id);
            if (!valida_objeto($compra)) {
                // mantém o contrato antigo
                return response()->json("Não permitido!!", 403);
            }

            // --- Config / Filial (com o mesmo comportamento do original) ---
            $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
            if ($compra->filial_id != null) {
                $config = Filial::findOrFail($compra->filial_id);
                if ($config->arquivo_certificado == null) {
                    // igual ao original: echo + die
                    echo "Necessário o certificado para realizar esta ação!";
                    die;
                }
            }

            // Se já existe XML autorizado pra esta chave, não retransmite
            if (!empty($compra->chave) && $this->xmlAutorizadoExiste($compra->chave)) {
                // devolve STRING simples (front antigo mostra no swal de sucesso)
                return response()->json('NF-e já autorizada (XML presente em disco).', 200);
            }

            $isFilial = $compra->filial_id;
            $cnpj     = preg_replace('/\D/', '', $config->cnpj);

            $nfe_service = new NFeEntradaService([
                "atualizacao" => date('Y-m-d h:i:s'),
                "tpAmb"       => (int)$config->ambiente,
                "razaosocial" => $config->razao_social,
                "siglaUF"     => $config->UF,
                "cnpj"        => $cnpj,
                "schemes"     => "PL_009_V4",
                "versao"      => "4.00",
                "tokenIBPT"   => "AAAAAAA",
                "CSC"         => $config->csc,
                "CSCid"       => $config->csc_id,
                "is_filial"   => $isFilial
            ], 55);

            header('Content-type: text/html; charset=UTF-8');
            $natureza = NaturezaOperacao::find($request->natureza);

            // --- Monta XML ---
            $nfe = $nfe_service->gerarNFe($compra, $natureza, $request->tipo_pagamento);
            if (isset($nfe['erros_xml'])) {
                // Mantém contrato antigo: 401 com STRING começando em 'Erro: '
                return response()->json('Erro: '.json_encode($nfe['erros_xml']), 401);
            }

            // Número gerado para esta tentativa (prioriza o já reservado na compra)
            $nNfGerado = isset($nfe['nNf']) ? (int)$nfe['nNf'] : null;
            if (!empty($compra->numero_emissao)) {
                $nNfGerado = (int) $compra->numero_emissao;
            }
            if (!$nNfGerado) {
                return response()->json('Erro: '.json_encode([
                        'cStat'   => 999,
                        'xMotivo' => 'Falha ao obter número da NF-e a partir do XML.'
                    ]), 401);
            }

            // Reserva/valida número se ainda não havia na compra
            if (empty($compra->numero_emissao)) {
                if ($this->numeroEmUso($nNfGerado, $this->empresa_id, $compra->id)) {
                    return response()->json('Erro: '.json_encode([
                            'cStat'   => 999,
                            'xMotivo' => "Número {$nNfGerado} já está em uso por outra NF-e."
                        ]), 401);
                }
                $compra->numero_emissao    = $nNfGerado;
                $config->ultimo_numero_nfe = $nNfGerado;
                $config->save();
            }

            // Chave desta tentativa (reutiliza a já gravada, senão usa a gerada)
            $chaveGerada    = $nfe['chave'] ?? null;
            $chaveTentativa = $compra->chave ?: $chaveGerada;

            // Se ainda não tem chave gravada, valida colisões e XML já existente em disco
            if (empty($compra->chave)) {
                if ($this->chaveEmUso($chaveTentativa, $this->empresa_id, $compra->id)) {
                    return response()->json('Erro: '.json_encode([
                            'cStat'   => 999,
                            'xMotivo' => "A chave {$chaveTentativa} já está em uso por outra NF-e."
                        ]), 401);
                }
                if ($this->xmlAutorizadoExiste($chaveTentativa)) {
                    return response()->json('Erro: '.json_encode([
                            'cStat'   => 999,
                            'xMotivo' => "Já existe XML autorizado em disco para esta chave."
                        ]), 401);
                }
            }

            // --- Assina e transmite ---
            $signed         = $nfe_service->sign($nfe['xml']);
            $resultadoBruto = $nfe_service->transmitir($signed, $chaveTentativa, [
                'document_type'      => 'NFeEntrada',
                'document_id'        => $compra->id,
                'document_reference' => 'compra:'.$compra->id,
                'numero'             => $nNfGerado,
                'serie'              => $config->numero_serie_nfe,
                'filial_id'          => $compra->filial_id,
                'ambiente'           => (int)$config->ambiente,
            ]);

            // Extrai cStat/xMotivo (sem mudar o payload para o front)
            [$cStat, $xMotivo] = $this->parseRetSefaz($resultadoBruto);

            // Se não veio cStat mas o XML autorizado apareceu em disco, considera Autorizado
            $xmlAutPath = public_path('xml_entrada_emitida/'.$chaveTentativa.'.xml');
            if ($cStat === null && is_file($xmlAutPath)) {
                $cStat   = 100;
                $xMotivo = $xMotivo ?: 'Autorizado (arquivo presente em disco).';
            }

            if ($resultadoBruto instanceof TransmissaoResult) {
                $resStr = (string)$resultadoBruto;
            } else {
                $resStr = is_string($resultadoBruto)
                    ? $resultadoBruto
                    : json_encode($resultadoBruto, JSON_UNESCAPED_UNICODE);
            }

            $statusPayload = [
                'sucesso'  => false,
                'status'   => 'rejeitado',
                'cStat'    => $cStat,
                'xMotivo'  => $xMotivo,
                'mensagem' => $xMotivo ?: 'Retorno não autorizado.',
                'raw'      => $resStr,
            ];

            // === AUTORIZADA (100/150/180) ===
            if ($cStat !== null && in_array((int)$cStat, [100,150,180], true)) {
                $compra->chave        = $chaveTentativa;
                $compra->estado       = 'APROVADO';
                $compra->data_emissao = date('Y-m-d H:i:s');
                $compra->save();

                // Importa no SIEG e envia e-mail (best-effort)
                try {
                    if (is_file($xmlAutPath)) {
                        $file = safe_file_get_contents($xmlAutPath);
                        importaXmlSieg($file, $this->empresa_id);
                    }
                } catch (\Throwable $t) {}
                $this->enviarEmailAutomatico($compra);

                $statusPayload['sucesso']  = true;
                $statusPayload['status']   = 'autorizado';
                $statusPayload['mensagem'] = $xMotivo ?: 'NF-e de entrada autorizada.';

                // Mantém contrato antigo: 200 com o retorno bruto (string)
                //return response()->json($resultadoBruto, 200);
                return response()->json($statusPayload, 200);
            }

            // === DENEGADA (110/301/302) - mantém número e chave ===
            if ($cStat !== null && in_array((int)$cStat, [110,301,302], true)) {
                if (empty($compra->chave)) {
                    $compra->chave = $chaveTentativa;
                }
                $compra->estado = 'DENEGADO';
                $compra->save();

                $statusPayload['status']   = 'denegado';
                $statusPayload['mensagem'] = $xMotivo ?: 'NF-e denegada.';
                $statusPayload['raw']      = $resStr;

                // Mantém contrato antigo: 200 com o retorno bruto (string)
                // return response()->json($resultadoBruto, 200);
                return response()->json($statusPayload, 200);
            }

            // === REJEITADA - mantém número/chave já reservados ===
            $compra->estado = 'REJEITADO';
            if (empty($compra->chave) && $chaveTentativa) {
                $compra->chave = $chaveTentativa;
            }
            $compra->save();

            $statusPayload['mensagem'] = $xMotivo ?: ($resStr ?: 'NF-e rejeitada pela SEFAZ.');
            // Mantém contrato antigo: 401 com STRING prefixada por "Erro: "
            //return response()->json('Erro: '.$resultadoBruto, 401);
            return response()->json($statusPayload, 401);

        } catch (\Throwable $e) {
            \Log::error('Erro em gerarEntrada', ['exception' => $e->getMessage()]);
            // Mantém contrato antigo em exceções
            return response()->json([
                'sucesso'  => false,
                'status'   => 'erro',
                'mensagem' => $e->getMessage(),
            ], 401);
        }
    }

    private function xmlAutorizadoExiste(?string $chave): bool
    {
        if (!$chave) return false;
        $p1 = public_path('xml_entrada_emitida/'.$chave.'.xml');
        $p2 = public_path('xml_entrada/'.$chave.'.xml');
        return is_file($p1) || is_file($p2);
    }

    private function chaveEmUso(?string $chave, int $empresaId, ?int $ignoreCompraId = null): bool
    {
        if (!$chave) return false;
        $q = \App\Models\Compra::where('empresa_id', $empresaId)->where('chave', $chave);
        if ($ignoreCompraId) $q->where('id', '!=', $ignoreCompraId);
        return $q->exists();
    }

    private function numeroEmUso(?int $numero, int $empresaId, ?int $ignoreCompraId = null): bool
    {
        if (!$numero) return false;
        $q = \App\Models\Compra::where('empresa_id', $empresaId)->where('numero_emissao', $numero);
        if ($ignoreCompraId) $q->where('id', '!=', $ignoreCompraId);
        return $q->exists();
    }

    private function parseRetSefaz($resultado): array
    {
        if ($resultado instanceof TransmissaoResult) {
            return [$resultado->getCStat(), $resultado->getMensagem()];
        }

        // JSON string?
        if (is_string($resultado)) {
            $j = json_decode($resultado, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($j)) {
                return $this->scanArrayRet($j);
            }

            // fallback: XML puro retornado pela SEFAZ
            if (stripos($resultado, '<retEnviNFe') !== false || stripos($resultado, '<protNFe') !== false) {
                try {
                    $std = new Standardize($resultado);
                    $arr = $std->toArray();
                    if (is_array($arr)) {
                        return $this->scanArrayRet($arr);
                    }
                } catch (\Throwable $t) {
                    // ignora e tenta regex simples
                }

                if (preg_match('/<cStat>([^<]+)<\\/cStat>.*<xMotivo>([^<]+)<\\/xMotivo>/is', $resultado, $m)) {
                    $cStat = (int) preg_replace('/\D/', '', $m[1] ?? '');
                    $xMot  = trim($m[2] ?? '');
                    return [$cStat ?: null, $xMot ?: null];
                }
            }
        }
        // array/stdClass
        $arr = is_array($resultado) ? $resultado
            : (is_object($resultado) ? json_decode(json_encode($resultado), true) : null);
        if (is_array($arr)) {
            return $this->scanArrayRet($arr);
        }
        return [null, null];
    }

    private function scanArrayRet(array $ret): array
    {
        $cStat = $ret['cStat'] ?? null;
        $xMot  = $ret['xMotivo'] ?? null;

        $buckets = [
            $ret,
            $ret['protNFe']['infProt'] ?? [],
            $ret['retEnviNFe']['protNFe']['infProt'] ?? [],
            $ret['infProt'] ?? [],
            $ret['retEvento']['infEvento'] ?? [],
            $ret['retEvento'] ?? [],
            $ret['raw']['retEvento']['infEvento'] ?? [],
            $ret['raw'] ?? [],
        ];

        foreach ($buckets as $b) {
            if (!is_array($b)) continue;
            if ($cStat === null && isset($b['cStat']))   $cStat = $b['cStat'];
            if ($xMot  === null && isset($b['xMotivo'])) $xMot  = $b['xMotivo'];
        }

        // normaliza cStat
        $cStat = is_numeric($cStat) ? (int)$cStat
            : (is_string($cStat) ? (int)preg_replace('/\D/', '', $cStat) : null);
        $xMot  = is_string($xMot) ? $xMot : null;

        return [$cStat, $xMot];
    }

    public function gerarEntradaWithXml(Request $request){
        $compra = Compra::find($request->id);
        if(valida_objeto($compra)){

            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();

            if($compra->filial_id != null){
                $config = Filial::findOrFail($compra->filial_id);
                if($config->arquivo_certificado == null){
                    echo "Necessário o certificado para realizar esta ação!";
                    die;
                }
            }
            $isFilial = $compra->filial_id;

            $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

            $nfe_service = new NFeEntradaService([
                "atualizacao" => date('Y-m-d h:i:s'),
                "tpAmb" => (int)$config->ambiente,
                "razaosocial" => $config->razao_social,
                "siglaUF" => $config->UF,
                "cnpj" => $cnpj,
                "schemes" => "PL_009_V4",
                "versao" => "4.00",
                "tokenIBPT" => "AAAAAAA",
                "CSC" => $config->csc,
                "CSCid" => $config->csc_id,
                "is_filial" => $isFilial
            ], 55);

            header('Content-type: text/html; charset=UTF-8');
            $natureza = NaturezaOperacao::find($compra->natureza_id);
            $xml = $request->xml;
            $exp = simplexml_load_string($xml);
            $array = json_decode(json_encode((array) $exp), true);

            $chave = (string)substr($array['infNFe']['@attributes']['Id'], 3, 47);
            $nNF = (string)$array['infNFe']['ide']['nNF'];

            $signed = $nfe_service->sign($xml);
            $resultado = $nfe_service->transmitir($signed, $chave, [
                'document_type'      => 'NFeEntrada',
                'document_id'        => $compra->id,
                'document_reference' => 'compra:'.$compra->id,
                'numero'             => (int)$nNF,
                'serie'              => $config->numero_serie_nfe,
                'filial_id'          => $compra->filial_id,
                'ambiente'           => (int)$config->ambiente,
            ]);

            if ($resultado instanceof TransmissaoResult) {
                if ($resultado->isSuccess() && !$resultado->isDenegado()) {
                    $compra->chave = $chave;
                    $compra->estado = 'APROVADO';
                    $compra->numero_emissao = $nNF;
                    $compra->data_emissao = date('Y-m-d H:i:s');

                    $compra->save();
                    $config->ultimo_numero_nfe = $nNF;
                    $config->save();
                    $this->enviarEmailAutomatico($compra);

                    $file = safe_file_get_contents(public_path('xml_entrada_emitida/'.$chave.'.xml'));
                    importaXmlSieg($file, $this->empresa_id);

                    return response()->json($resultado, 200);
                }

                if ($resultado->isDenegado()) {
                    $compra->chave = $chave;
                    $compra->estado = 'DENEGADO';
                    $compra->numero_emissao = $nNF;
                    $compra->data_emissao = date('Y-m-d H:i:s');
                    $compra->save();

                    $config->ultimo_numero_nfe = $nNF;
                    $config->save();

                    return response()->json($resultado, 200);
                }

                $compra->estado = 'REJEITADO';
                $compra->save();

                return response()->json($resultado, $resultado->httpStatus());
            }

            if (is_string($resultado) && substr($resultado, 0, 4) != 'Erro') {
                $compra->chave = $chave;
                $compra->estado = 'APROVADO';
                $compra->numero_emissao = $nNF;
                $compra->data_emissao = date('Y-m-d H:i:s');

                $compra->save();
                $config->ultimo_numero_nfe = $nNF;
                $config->save();
                $this->enviarEmailAutomatico($compra);

                $file = safe_file_get_contents(public_path('xml_entrada_emitida/'.$chave.'.xml'));
                importaXmlSieg($file, $this->empresa_id);

                return response()->json($resultado, 200);
            }

            $compra->estado = 'REJEITADO';
            $compra->save();
            return response()->json($resultado, 401);
        }else{
            return response()->json("Não permitido!!", 403);

        }
    }

    public function imprimir($id){
        $compra = Compra::find($id);
        if(valida_objeto($compra)){

            //$public = env('SERVIDOR_WEB') ? 'public/' : '';
            $public = rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR;
            $xml = null;
            if(file_exists(public_path('xml_entrada_emitida/').$compra->chave.'.xml')){
                $xml = safe_file_get_contents(public_path('xml_entrada_emitida/').$compra->chave.'.xml');
            }else if(file_exists(public_path('xml_entrada/').$compra->chave.'.xml')){
                $xml = safe_file_get_contents($public.'xml_entrada/'.$compra->chave.'.xml');
            }else{
                session()->flash('mensagem_erro', 'Xml não encontrado!');
                return redirect('/compras');
            }
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();

            if($config->logo){
                $logo = 'data://text/plain;base64,'. base64_encode(safe_file_get_contents(public_path('logos/') . $config->logo));
            }else{
                $logo = null;
            }
        // $docxml = FilesFolders::readFile($xml);

            try {
                $danfe = new Danfe($xml);
                // $id = $danfe->monta($logo);
                $pdf = $danfe->render($logo);
                header('Content-Type: application/pdf');
                // echo $pdf;

                return response($pdf)
                ->header('Content-Type', 'application/pdf');
            } catch (InvalidArgumentException $e) {
                echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
            }
        }else{
            return redirect('/403');
        }
    }

    public function imprimirCce($id)
    {
        $compra = Compra::where('id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        if (!$compra) {
            return response('Compra não encontrada', 404);
        }

        if ((int)$compra->sequencia_cce <= 0) {
            return response('<center><h1>Este documento não possui evento de correção!<h1></center>', 200);
        }

        // Base do diretório público
        $dir = rtrim(public_path('xml_nfe_entrada_correcao'), '/\\');
        $chave = $compra->chave;
        $seq   = (int)$compra->sequencia_cce;

        // Candidatos em ordem de preferência
        $candidatos = [
            $dir . DIRECTORY_SEPARATOR . "{$chave}-cce-{$seq}.xml",     // padrão atual
            $dir . DIRECTORY_SEPARATOR . "{$chave}-cce-0{$seq}.xml",    // caso tenha zero à esquerda
        ];

        // Se não encontrou os candidatos diretos, procure o maior -cce-*.xml dessa chave
        $arquivo = null;
        foreach ($candidatos as $path) {
            if (is_file($path)) {
                $arquivo = $path;
                break;
            }
        }

        if (!$arquivo) {
            $matches = glob($dir . DIRECTORY_SEPARATOR . "{$chave}-cce*.xml");
            if (!empty($matches)) {
                // Se houver vários, escolher o de MAIOR sequência
                usort($matches, function ($a, $b) {
                    $na = preg_match('/-cce-(\d+)\.xml$/', $a, $ma) ? (int)$ma[1] : -1;
                    $nb = preg_match('/-cce-(\d+)\.xml$/', $b, $mb) ? (int)$mb[1] : -1;
                    return $nb <=> $na;
                });
                $arquivo = $matches[0];
            }
        }

        // Fallback antigo (sem sufixo)
        if (!$arquivo) {
            $fallback = $dir . DIRECTORY_SEPARATOR . "{$chave}.xml";
            if (is_file($fallback)) {
                $arquivo = $fallback;
            }
        }

        if (!$arquivo) {
            return response("Arquivo XML não encontrado!!", 404);
        }

        $xml = @safe_file_get_contents($arquivo);
        if ($xml === false) {
            return response("Falha ao ler o XML em: {$arquivo}", 500);
        }

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $logo = null;
        if ($config && $config->logo) {
            $logoPath = rtrim(public_path('logos'), '/\\') . DIRECTORY_SEPARATOR . $config->logo;
            if (is_file($logoPath)) {
                $logo = 'data://text/plain;base64,' . base64_encode(safe_file_get_contents($logoPath));
            }
        }

        $dadosEmitente = $this->getEmitente($compra->filial);

        try {
            $daevento = new Daevento($xml, $dadosEmitente);
            $daevento->debugMode(true);
            $pdf = $daevento->render($logo);

            return response($pdf)->header('Content-Type', 'application/pdf');
        } catch (InvalidArgumentException $e) {
            return response("Ocorreu um erro durante o processamento: " . $e->getMessage(), 500);
        }
    }

    private function getEmitente($config = null){
        if($config == null){
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();
        }
        return [
            'razao' => $config->razao_social,
            'logradouro' => $config->logradouro,
            'numero' => $config->numero,
            'complemento' => '',
            'bairro' => $config->bairro,
            'CEP' => $config->cep,
            'municipio' => $config->municipio,
            'UF' => $config->UF,
            'telefone' => $config->telefone,
            'email' => ''
        ];
    }

    public function cancelarEntrada(Request $request)
    {
        try {
            $compra = \App\Models\Compra::findOrFail($request->compra_id);

            // --- Config / filial ---
            $config = \App\Models\ConfigNota::where('empresa_id', $this->empresa_id)->first();
            $isFilial = $compra->filial_id;
            if ($isFilial) {
                $config = \App\Models\Filial::findOrFail($isFilial);
            }
            $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

            // --- Service ---
            $nfe_service = new \App\Services\NFeEntradaService([
                "atualizacao" => date('Y-m-d h:i:s'),
                "tpAmb"       => (int)$config->ambiente,
                "razaosocial" => $config->razao_social,
                "siglaUF"     => $config->UF,
                "cnpj"        => $cnpj,
                "schemes"     => "PL_009_V4",
                "versao"      => "4.00",
                "tokenIBPT"   => "AAAAAAA",
                "CSC"         => $config->csc,
                "CSCid"       => $config->csc_id,
                "is_filial"   => $isFilial
            ], 55);

            // --- Chamada SEFAZ ---
            $rawRet = $nfe_service->cancelar($compra, $request->justificativa);

            // Pode vir string JSON, array ou string simples
            $data = is_string($rawRet) && $this->isJson($rawRet) ? json_decode($rawRet, true)
                : (is_array($rawRet) ? $rawRet : ['message' => (string)$rawRet]);

            $cStatEnvelope = $data['cStatEnvelope'] ?? null;
            $cStatEvento   = $data['cStatEvento']   ?? null;
            $xMotivoEvento = $data['xMotivoEvento'] ?? null;
            $xmlPath       = $data['xmlPath']       ?? null;
            $okFlag        = (bool)($data['ok'] ?? false);
            $stage         = $data['stage'] ?? null;     // "exception" quando erro de comunicação
            $excMsg        = $data['message'] ?? null;

            $raw      = $data['raw'] ?? [];
            $infEvRaw = $raw['retEvento']['infEvento'] ?? ($raw['infEvento'] ?? []);
            $cStatInf = $infEvRaw['cStat']           ?? null;
            $xMotInf  = $infEvRaw['xMotivo']         ?? null;
            $tpAmb    = $infEvRaw['tpAmb']           ?? ($raw['tpAmb'] ?? null);
            $verAplic = $infEvRaw['verAplic']        ?? ($raw['verAplic'] ?? null);
            $dhRecbto = $infEvRaw['dhRegEvento']     ?? ($infEvRaw['dhRecbto'] ?? null);
            $cUF      = $infEvRaw['cOrgao']          ?? ($raw['cOrgao'] ?? null);
            $chNFe    = $infEvRaw['chNFe']           ?? $compra->chave;

            // Aprovado somente em 101/135/155
            $codes    = array_filter([$cStatEvento, $cStatInf, $cStatEnvelope]);
            $aprovado = in_array('101', $codes, true) || in_array('135', $codes, true) || in_array('155', $codes, true);

            $mensagem = ($stage === 'exception')
                ? ('Falha ao comunicar com a SEFAZ: ' . ($excMsg ?: 'erro desconhecido.'))
                : ($xMotivoEvento ?: $xMotInf ?: ($okFlag ? 'Cancelamento processado.' : 'Cancelamento rejeitado.'));

            // Atualiza estado/estoque apenas se aprovado
            if ($aprovado) {
                $stockMove = new \App\Helpers\StockMove();
                foreach ($compra->itens as $i) {
                    $stockMove->downStock(
                        $i->produto->id,
                        $i->quantidade * $i->produto->conversao_unitaria,
                        $compra->filial_id
                    );
                }
                $compra->estado = 'CANCELADO';
                $compra->save();

                if (!$xmlPath) {
                    $xmlPath = public_path('xml_nfe_entrada_cancelada/'.$compra->chave.'.xml');
                }
                if (is_string($xmlPath) && file_exists($xmlPath)) {
                    try {
                        $file = safe_file_get_contents($xmlPath);
                        importaXmlSieg($file, $this->empresa_id);
                    } catch (\Throwable $e) {
                        \Log::warning('Falha ao importar XML de cancelamento no SIEG', [
                            'compra_id' => $compra->id,
                            'xmlPath'   => $xmlPath,
                            'error'     => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Payload completo para o frontend
            $payload = [
                'ok'               => $okFlag,
                'aprovado'         => $aprovado,
                'mensagem'         => $mensagem,
                'toast_type'       => $aprovado ? 'success' : ($stage === 'exception' ? 'error' : 'warning'),
                'compra_id'        => $compra->id,
                'chave'            => $chNFe,

                'cStatEnvelope'    => $cStatEnvelope,
                'cStatEvento'      => $cStatEvento,
                'xMotivoEvento'    => $xMotivoEvento,

                'cStatInfEvento'   => $cStatInf,
                'xMotivoInfEvento' => $xMotInf,
                'tpAmb'            => $tpAmb,
                'verAplic'         => $verAplic,
                'dhRecbto'         => $dhRecbto,
                'cUF'              => $cUF,

                'xmlPath'          => $xmlPath,
                'stage'            => $stage,
                'message'          => $excMsg,
                'raw'              => $raw,
            ];

            // === TEXTO para o modal (sempre string) ===
            $textoModal = $this->formatCancelMessage($payload);

            // IMPORTANTE: como o front do projeto geralmente mostra "Sucesso ..." quando 200,
            // prefixamos "Erro: " nos cenários não aprovados para aparecer como rejeição no modal.
            if (!$aprovado) {
                $textoModal = 'Erro: ' . $textoModal;
            }

            // === RESPOSTA (SEM mudar status code) ===
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json($textoModal, 200); // <<<<< STRING para o swal
            }

            // Fluxo não-AJAX (mantém como está)
            if ($aprovado) {
                session()->flash('mensagem_sucesso', 'Cancelamento aprovado: '.$payload['mensagem']);
            } else {
                $det = [];
                foreach (['cStatEnvelope','cStatEvento','cStatInfEvento'] as $k) {
                    if (!empty($payload[$k])) $det[] = $k.'='.$payload[$k];
                }
                $detStr = $det ? (' ['.implode(', ', $det).']') : '';
                session()->flash('mensagem_erro', 'Cancelamento rejeitado: '.$payload['mensagem'].$detStr);
            }
            return redirect()->back();

            if (!$aprovado) {
                \Log::info('Cancelamento rejeitado', ['retorno' => $payload, 'compra_id' => $compra->id]);
            }

            // === RESPOSTA ===
            if ($request->ajax() || $request->expectsJson()) {
                // IMPORTANTE: SEMPRE 200 para não acionar o modal "Algo deu errado"
                return response()->json($payload, 200);
            }

            // Fluxo não-AJAX: flash + redirect
            if ($aprovado) {
                session()->flash('mensagem_sucesso', 'Cancelamento aprovado: '.$mensagem);
            } else {
                $det = [];
                foreach (['cStatEnvelope','cStatEvento','cStatInfEvento'] as $k) {
                    if (!empty($payload[$k])) $det[] = $k.'='.$payload[$k];
                }
                $detStr = $det ? (' ['.implode(', ', $det).']') : '';
                session()->flash('mensagem_erro', 'Cancelamento rejeitado: '.$mensagem.$detStr);
            }
            return redirect()->back();

        } catch (\Throwable $e) {
            \Log::error('Erro ao cancelar NFe de entrada', [
                'compra_id' => $request->compra_id ?? null,
                'exception' => $e->getMessage(),
            ]);

            if ($request->ajax() || $request->expectsJson()) {
                // Mesmo em erro inesperado, responde 200 para o front não cair no modal genérico
                return response()->json([
                    'ok'        => false,
                    'aprovado'  => false,
                    'mensagem'  => 'Erro ao cancelar: '.$e->getMessage(),
                    'toast_type'=> 'error',
                    'stage'     => 'exception',
                    'compra_id' => $request->compra_id ?? null,
                ], 200);
            }

            session()->flash('mensagem_erro', 'Erro ao cancelar: '.$e->getMessage());
            return redirect()->back();
        }
    }

    private function formatCancelMessage(array $p): string
    {
        // tenta pegar o código/motivo do lugar mais confiável
        $cStat = $p['cStatEvento'] ?? $p['cStatInfEvento'] ?? $p['cStatEnvelope'] ?? null;
        $xMot  = $p['xMotivoEvento'] ?? $p['xMotivoInfEvento'] ?? $p['mensagem'] ?? null;

        // fallback a partir do raw (casos mais raros)
        if (!$cStat || !$xMot) {
            $inf = $p['raw']['retEvento']['infEvento'] ?? ($p['raw']['infEvento'] ?? []);
            if (!$cStat && isset($inf['cStat']))   $cStat = $inf['cStat'];
            if (!$xMot  && isset($inf['xMotivo'])) $xMot  = $inf['xMotivo'];
        }

        // normalizações
        if (is_array($cStat)) $cStat = reset($cStat);
        if (is_array($xMot))  $xMot  = reset($xMot);
        $cStat = is_scalar($cStat) ? (string)$cStat : null;
        $xMot  = is_scalar($xMot)  ? (string)$xMot  : 'Retorno sem motivo.';

        $ch = $p['chave'] ?? null;
        $extra = [];
        if (!empty($p['verAplic'])) $extra[] = 'verAplic: '.$p['verAplic'];
        if (!empty($p['dhRecbto'])) $extra[] = 'dhRecbto: '.$p['dhRecbto'];
        $sufixo = $extra ? ' (' . implode(' | ', $extra) . ')' : '';

        // Ex.: "[135] : Evento registrado e vinculado a NF-e (verAplic: X.Y.Z | dhRecbto: 2025-10-16T12:34:56-03:00)"
        $base = '['.($cStat ?: '---').'] : '.$xMot.$sufixo;

        // se tiver chave, agrega ao fim (ajuda o usuário)
        return $ch ? ($base.' | CHAVE: '.$ch) : $base;
    }

    public function cartaCorrecao(Request $request)
    {
        $validated = $request->validate([
            'id'       => 'required|integer|exists:compras,id',
            'correcao' => 'required|string|min:3|max:1000',
        ]);

        $compra = Compra::find($validated['id']);

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        if ($compra->filial_id) {
            $config = Filial::findOrFail($compra->filial_id);
        }

        $cnpj = preg_replace('/\D/', '', $config->cnpj);

        $nfe_service = new NFeEntradaService([
            "atualizacao" => date('Y-m-d H:i:s'),
            "tpAmb"       => (int) $config->ambiente,
            "razaosocial" => $config->razao_social,
            "siglaUF"     => $config->UF,
            "cnpj"        => $cnpj,
            "schemes"     => "PL_009_V4",
            "versao"      => "4.00",
            "tokenIBPT"   => "AAAAAAA",
            "CSC"         => $config->csc,
            "CSCid"       => $config->csc_id,
            "is_filial"   => $compra->filial_id,
        ], 55);

        try {
            $resp = $nfe_service->cartaCorrecao($validated['id'], $validated['correcao']);

            // Se o service retorna string JSON, decodamos uma vez só:
            if (is_string($resp)) {
                $decoded = json_decode($resp, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return response()->json($decoded, 200);
                }
            }

            // Senão, retornamos o que veio (array/stdClass) como JSON:
            return response()->json($resp, 200);

        } catch (\Throwable $e) {
            return response()->json([
                'erro'     => true,
                'mensagem' => $e->getMessage(),
            ], 422);
        }
    }

    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function produtosSemValidade(){
        $produtos = Produto::select('id')
        ->where('alerta_vencimento', '>', 0)
        ->where('empresa_id', $this->empresa_id)
        ->get();

        $estoque = ItemCompra::where('validade', NULL)
        ->limit(100)->get();

        $itensSemEstoque = [];
        foreach($estoque as $e){
            foreach($produtos as $p){
                if($p->id == $e->produto_id && $e->produto->empresa_id == $this->empresa_id){
                    array_push($itensSemEstoque, $e);
                }
            }
        }

        return view('compraManual/itens_sem_estoque')
        ->with('itens', $itensSemEstoque)
        ->with('title', 'Itens sem Estoque');
    }

    public function salvarValidade(Request $request){
        $tamanhoArray = $request->tamanho_array;
        $contErro = 0;

        for($aux = 0; $aux < $tamanhoArray; $aux++){

            $validade = str_replace("/", "-", $request->input('validade_'.$aux));
            $id = $request->input('id_'.$aux);

            if(strlen($validade) == 10){ // tamanho data ok
                $item = ItemCompra::find($id);
                $dataHoje = strtotime(date('Y-m-d'));
                $validadeForm = strtotime(\Carbon\Carbon::parse($validade)->format('Y-m-d'));
                if($validadeForm > $dataHoje){ // confirma data futura
                    $item->validade = \Carbon\Carbon::parse($validade)->format('Y-m-d');
                    $item->save();
                }else{
                    $contErro++;
                }
            }else{
                $contErro++;
            }
        }
        if($contErro == 0){
            session()->flash('mensagem_sucesso', 'Validades inseridas para os itens!');
        }else{
            session()->flash('mensagem_erro', 'Erro no formulário para os itens abaixo!');
        }
        return redirect('/compras/produtosSemValidade');

    }

    public function validadeAlerta(){
        $dataHoje = date('Y-m-d', strtotime("-30 days",strtotime(date('Y-m-d'))));
        $dataFutura = date('Y-m-d', strtotime("+30 days",strtotime(date('Y-m-d'))));
        // $produtos = Produto::select('id')->where('alerta_vencimento', '>', 0)->get();
        $itensCompra = ItemCompra::
        whereBetween('validade', [$dataHoje, $dataFutura])
        ->limit(300)->get();
        $itens = [];
        foreach($itensCompra as $i){
            $strValidade = strtotime($i->validade);
            $strHoje = strtotime(date('Y-m-d'));
            $dif = $strValidade - $strHoje;
            $dif = $dif/24/60/60;

            if($dif <= $i->produto->alerta_vencimento && $i->produto->empresa_id == $this->empresa_id){
                array_push($itens, $i);
            }
        }

        return view('compraManual/validade_alerta')
        ->with('itens', $itens)
        ->with('title', 'Produtos com validade próxima');
    }

    public function xmlTemporaria(Request $request){
        $compra = Compra::find($request->id);

        if(valida_objeto($compra)){

            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();

            if($compra->filial_id != null){
                $config = Filial::findOrFail($compra->filial_id);
                if($config->arquivo_certificado == null){
                    echo "Necessário o certificado para realizar esta ação!";
                    die;
                }
            }
            $isFilial = $compra->filial_id;

            $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

            $nfe_service = new NFeEntradaService([
                "atualizacao" => date('Y-m-d h:i:s'),
                "tpAmb" => (int)$config->ambiente,
                "razaosocial" => $config->razao_social,
                "siglaUF" => $config->UF,
                "cnpj" => $cnpj,
                "schemes" => "PL_009_V4",
                "versao" => "4.00",
                "tokenIBPT" => "AAAAAAA",
                "CSC" => $config->csc,
                "CSCid" => $config->csc_id,
                "is_filial" => $isFilial
            ], 55);

            header('Content-type: text/html; charset=UTF-8');
            $natureza = NaturezaOperacao::find($request->natureza);

            $nfe = $nfe_service->gerarNFe($compra, $natureza, $request->tipo_pagamento);
            if(!isset($nfe['erros_xml'])){

                $config = ConfigNota::
                where('empresa_id', $this->empresa_id)
                ->first();
                //$public = env('SERVIDOR_WEB') ? 'public/' : '';
                $public = rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR;
                if($config->logo){
                    $logo = 'data://text/plain;base64,'. base64_encode(safe_file_get_contents($public.'logos/' . $config->logo));
                }else{
                    $logo = null;
                }

                $danfe = new Danfe($nfe['xml']);
            // $id = $danfe->monta($logo);
                $pdf = $danfe->render($logo);
                return response($nfe['xml'])
                ->header('Content-Type', 'application/xml');
            }else{
                print_r($nfe['erros_xml']);
            }
        }
    }

    public function danfeTemporaria(Request $request){
        $compra = Compra::find($request->id);

        if(valida_objeto($compra)){

            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
                ->first();

            if($compra->filial_id != null){
                $config = Filial::findOrFail($compra->filial_id);
                if($config->arquivo_certificado == null){
                    echo "Necessário o certificado para realizar esta ação!";
                    die;
                }
            }
            $isFilial = $compra->filial_id;

            $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

            $nfe_service = new NFeEntradaService([
                "atualizacao" => date('Y-m-d h:i:s'),
                "tpAmb" => (int)$config->ambiente,
                "razaosocial" => $config->razao_social,
                "siglaUF" => $config->UF,
                "cnpj" => $cnpj,
                "schemes" => "PL_009_V4",
                "versao" => "4.00",
                "tokenIBPT" => "AAAAAAA",
                "CSC" => $config->csc,
                "CSCid" => $config->csc_id,
                "is_filial" => $isFilial
            ], 55);

            header('Content-type: text/html; charset=UTF-8');
            $natureza = NaturezaOperacao::find($request->natureza);

            $nfe = $nfe_service->gerarNFe($compra, $natureza, $request->tipo_pagamento);
            if(!isset($nfe['erros_xml'])){

                $config = ConfigNota::
                where('empresa_id', $this->empresa_id)
                    ->first();
                //$public = env('SERVIDOR_WEB') ? 'public/' : '';
                $public = rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR;

                if($config->logo){
                    $logo = 'data://text/plain;base64,'. base64_encode(safe_file_get_contents(public_path('logos/') . $config->logo));
                }else{
                    $logo = null;
                }

                $xml = $nfe['xml'];
                if (strpos($xml, '<tpImp>') === false) {
                    $tpImp = isset($config->tipo_impressao_danfe) && !empty($config->tipo_impressao_danfe)
                        ? $config->tipo_impressao_danfe
                        : 1;
                    $xml = str_replace('<ide>', "<ide><tpImp>{$tpImp}</tpImp>", $xml);
                }

                $danfe = new Danfe($xml);
                //$id = $danfe->monta($logo);
                $pdf = $danfe->render($logo);
                return response($pdf)
                    ->header('Content-Type', 'application/pdf');
            }else{
                print_r($nfe['erros_xml']);
            }

            // return response($nfe['xml'])
            // ->header('Content-Type', 'application/xml');
        }
    }

    public function consultar(Request $request){

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $compra = Compra::find($request->compra_id);

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        $nfe_service = new NFeEntradaService([
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb" => (int)$config->ambiente,
            "razaosocial" => $config->razao_social,
            "siglaUF" => $config->UF,
            "cnpj" => $cnpj,
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
            "tokenIBPT" => "AAAAAAA",
            "CSC" => $config->csc,
            "CSCid" => $config->csc_id
        ], 55);

        $c = $nfe_service->consultar($compra);
        echo json_encode($c);

    }

    public function salvarChaveRef(Request $request){
        try{

            CompraReferencia::create([
                'compra_id' => $request->compra_id,
                'chave' => $request->chave
            ]);
            session()->flash('mensagem_sucesso', "Chave referenciada!");
        }catch(\Exception $e){
            session()->flash('mensagem_erro', "Erro: " . $e->getMessage());
        }
        return redirect()->back();
    }

    public function deleteChave($id){
        try{

            CompraReferencia::find($id)->delete();
            session()->flash('mensagem_sucesso', "Chave removida!");
        }catch(\Exception $e){
            session()->flash('mensagem_erro', "Erro: " . $e->getMessage());
        }
        return redirect()->back();
    }

    private function enviarEmailAutomatico($compra){
        $escritorio = EscritorioContabil::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($escritorio != null && $escritorio->envio_automatico_xml_contador){
            $email = $escritorio->email;
            Mail::send('mail.xml_automatico', ['descricao' => 'Envio de NFe Entrada'], function($m) use ($email, $compra){
                $nomeEmpresa = env('MAIL_NAME');
                $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
                $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
                $emailEnvio = env('MAIL_USERNAME');

                $m->from($emailEnvio, $nomeEmpresa);
                $m->subject('Envio de XML Automático');

                $m->attach(public_path('xml_entrada_emitida/'.$compra->chave.'.xml'));
                $m->to($email);
            });
        }
    }

    public function estadoFiscal($id){
        $compra = Compra::
        where('id', $id)
        ->first();
        $value = session('user_logged');
        if($value['adm'] == 0) return redirect()->back();
        if(valida_objeto($compra)){

            return view("compraManual/alterar_estado_fiscal")
            ->with('compra', $compra)
            ->with('title', "Alterar estado compra $id");
        }else{
            return redirect('/403');
        }
    }

    public function estadoFiscalStore(Request $request){
        try{
            $compra = Compra::find($request->compra_id);
            $estado = $request->estado;

            $compra->estado = $estado;
            if ($request->hasFile('file')){
                $public = rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR;

                // Obtem o caminho temporário do arquivo para que o simplexml_load_file consiga lê-lo
                $filePath = $request->file('file')->getRealPath();
                $xml = simplexml_load_file($filePath);

                // Verifica se a estrutura XML esperada existe
                if(!$xml || !isset($xml->NFe) || !isset($xml->NFe->infNFe)){
                    session()->flash('mensagem_erro', "XML inválido ou com estrutura incorreta!");
                    return redirect()->back();
                }

                // Se necessário, verifique se os atributos existem antes de acessá-los
                $infNFe = $xml->NFe->infNFe;
                if(!$infNFe->attributes()){
                    session()->flash('mensagem_erro', "Não foi possível encontrar os atributos no XML!");
                    return redirect()->back();
                }

                $chave = substr($infNFe->attributes()->Id, 3, 44);

                // Move o arquivo para a pasta de destino com o nome definido
                $request->file('file')->move(public_path('xml_entrada_emitida'), $chave . '.xml');

                $compra->chave = $chave;
                $compra->numero_emissao = (int)$xml->NFe->infNFe->ide->nNF;
            }

            $compra->save();
            session()->flash("mensagem_sucesso", "Estado alterado");
        } catch(\Exception $e){
            session()->flash("mensagem_erro", "Erro: " . $e->getMessage());
        }
        return redirect()->back();
    }

     public function setNaturezaPagamento(Request $request){
        $compra = Compra::find($request->id);

        $compra->tipo_pagamento = $request->tipo_pagamento;
        $compra->natureza_id = $request->natureza_id;
        $compra->save();
        return "sucesso";
    }

    public function print($id){
        $compra = Compra::find($id);
        if(valida_objeto($compra)){
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();
            $p = view('compraManual/print')
            ->with('config', $config)
            ->with('compra', $compra);
            // return $p;

            $domPdf = new Dompdf(["enable_remote" => true]);
            $domPdf->loadHtml($p);

            $pdf = ob_get_clean();

            $domPdf->setPaper("A4");
            $domPdf->render();
            $domPdf->stream("Pedido de Compra $id.pdf", array("Attachment" => false));
        }else{
            return redirect('/403');
        }
    }

    public function print80($id){
        $compra = Compra::findOrFail($id);
        if(valida_objeto($compra)){
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();

            $cupom = new CompraPrint80($compra);
            $cupom->monta();
            $pdf = $cupom->render();
            return response($pdf)
            ->header('Content-Type', 'application/pdf');
        }else{
            return redirect('/403');
        }
    }

    public function imprimirPedidoCompra80mm($id)
    {
        try {
            // Busca o pedido de compra com os relacionamentos necessários
            $compra = Compra::with(['fornecedor', 'itens.produto'])
                ->where('empresa_id', $this->empresa_id)
                ->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            // Registra erro no log
            \Log::error('Erro ao buscar pedido de compra', [
                'id' => $id,
                'exception' => $e->getMessage(),
            ]);

            // Define uma mensagem amigável para o usuário na sessão
            session()->flash('mensagem_erro', "O pedido de compra com ID #{$id} não foi encontrado. Verifique o ID e tente novamente.");

            // Log da mensagem salva na sessão
            \Log::info('Mensagem de erro definida na sessão', session()->all());

            // Redireciona para a página anterior ou para a lista de pedidos
            return request()->headers->get('referer') ? redirect()->back() : redirect()->route('compras.list');
        }

        // Cria uma instância do relatório
        $relatorio = new PedidoCompraPrint80($compra);

        // Monta o relatório
        $relatorio->monta();

        // Gera o PDF
        $pdf = $relatorio->render();

        // Retorna o PDF com os cabeçalhos HTTP corretos
        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="pedido_compra_80mm.pdf"')
            ->header('Cache-Control', 'no-cache, must-revalidate');
    }

    public function etiqueta($id){
        $compra = Compra::findOrFail($id);
        if(valida_objeto($compra)){
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();

            $padrosEtiqueta = Etiqueta::
            where('empresa_id', null)
            ->orWhere('empresa_id', $this->empresa_id)
            ->get();

            return view('compraManual/etiqueta')
            ->with('compra', $compra)
            ->with('padrosEtiqueta', $padrosEtiqueta)
            ->with('title', 'Gerar Etiqueta');
        }else{
            return redirect('/403');
        }
    }

    public function etiquetaStore(Request $request){

        $this->_validateEtiqueta($request);

        // print_r($request->all());
        // die;
        try{

            $files = glob(public_path("barcode/*"));

            foreach($files as $file){
                if(is_file($file)) {
                    unlink($file);
                }
            }

            $compra = Compra::findOrFail($request->compra_id);
            $data = [];
            $cont = 0;
            foreach($compra->itens as $it){
                if($request['prod_select_'.$it->id]){
                    $cont++;
                    $produto = $it->produto;
                    $nome = $produto->nome . " " . $produto->str_grade;
                    $codigo = $produto->codBarras;
                    $valor = $produto->valor_venda;
                    $unidade = $produto->unidade_venda;

                    if($codigo == "" || $codigo == "SEM GTIN" || $codigo == "sem gtin"){
                        session()->flash('mensagem_erro', "Produto $nome sem código de barras definido");
                        return redirect()->back();
                    }

                    $rand = rand(1000, 9999);
                    $item = [
                        'nome_empresa' => $request->nome_empresa ? true : false,
                        'nome_produto' => $request->nome_produto ? true : false,
                        'valor_produto' => $request->valor_produto ? true : false,
                        'cod_produto' => $request->cod_produto ? true : false,
                        'codigo_barras_numerico' => $request->codigo_barras_numerico ? true : false,
                        'nome' => $nome,
                        'codigo_barras' => $codigo,
                        'codigo' => $produto->id . ($produto->referencia != '' ? ' | REF'.$produto->referencia : ''),
                        'valor' => $valor,
                        'unidade' => $unidade,
                        'empresa' => $produto->empresa->nome,
                        'rand' => $rand
                    ];

                    $generatorPNG = new \Picqer\Barcode\BarcodeGeneratorPNG();

                    $bar_code = $generatorPNG->getBarcode($codigo, $generatorPNG::TYPE_EAN_13);

                    file_put_contents(public_path("barcode")."/$rand.png", $bar_code);

                    for($i=0; $i<$it->quantidade; $i++){
                        array_push($data, $item);
                    }
                }
            }

            if($cont == 0){
                session()->flash('mensagem_erro', 'Seleceione ao menos um produto para imprimir');
                return redirect()->back();
            }

            $qtdLinhas = $request->qtd_linhas;
            $qtdTotal = $request->qtd_etiquetas;

            return view('compraManual/print_etiqueta')
            ->with('altura', $request->altura)
            ->with('largura', $request->largura)
            ->with('codigo', $codigo)
            ->with('quantidade', $qtdTotal)
            ->with('distancia_topo', $request->dist_topo)
            ->with('distancia_lateral', $request->dist_lateral)
            ->with('quantidade_por_linhas', $qtdLinhas)
            ->with('tamanho_fonte', $request->tamanho_fonte)
            ->with('tamanho_codigo', $request->tamanho_codigo)
            ->with('data', $data);
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    private function _validateEtiqueta(Request $request){
        $rules = [
            'largura' => 'required',
            'altura' => 'required',
            'qtd_linhas' => 'required',
            'dist_lateral' => 'required',
            'dist_topo' => 'required',
            'tamanho_fonte' => 'required',
            'tamanho_codigo' => 'required',
        ];

        $messages = [
            'largura.required' => 'Campo obrigatório.',
            'altura.required' => 'Campo obrigatório.',
            'qtd_linhas.required' => 'Campo obrigatório.',
            'dist_lateral.required' => 'Campo obrigatório.',
            'dist_topo.required' => 'Campo obrigatório.',
            'qtd_etiquetas.required' => 'Campo obrigatório.',
            'tamanho_fonte.required' => 'Campo obrigatório.',
            'tamanho_codigo.required' => 'Campo obrigatório.',

        ];
        $this->validate($request, $rules, $messages);
    }

    public function editXml(Request $request){
        $id = $request->id;
        $natureza = $request->natureza;
        $tipo_pagamento = $request->tipo_pagamento;


        $item = Compra::findOrFail($id);

        $item->tipo_pagamento = $request->tipo_pagamento;
        $item->natureza_id = $request->natureza;
        $item->save();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        if($item->filial_id != null){
            $config = Filial::findOrFail($item->filial_id);
            if($config->arquivo_certificado == null){
                echo "Necessário o certificado para realizar esta ação!";
                die;
            }
        }
        $isFilial = $item->filial_id;

        $nfe_service = new NFeEntradaService([
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb" => (int)$config->ambiente,
            "razaosocial" => $config->razao_social,
            "siglaUF" => $config->UF,
            "cnpj" => $cnpj,
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
            "tokenIBPT" => "AAAAAAA",
            "CSC" => $config->csc,
            "CSCid" => $config->csc_id,
            "is_filial" => $isFilial
        ], 55);

        $natureza = NaturezaOperacao::find($request->natureza);
        $nfe = $nfe_service->gerarNFe($item, $natureza, $request->tipo_pagamento);

        if(!isset($nfe['erros_xml'])){
            $xml = $nfe['xml'];

            return view('compraManual.edit_xml', compact('item', 'xml'))
            ->with('title', 'Editando XML');
        }else{
            print_r($nfe['erros_xml']);
        }
    }

    public function setarValidade($id){
        $item = Compra::findOrFail($id);

        return view('compraManual/setar_validade')
        ->with('item', $item)
        ->with('title', 'Setar validade dos itens');
    }

    public function setarValidadeStore(Request $request){
        try{
            for($i=0; $i<sizeof($request->validade); $i++){
                $item = ItemCompra::findOrFail($request->item_id[$i]);
                $item->validade = $request->validade[$i];
                $item->save();
            }
            session()->flash('mensagem_sucesso', 'Validade setada!');
            return redirect('/compras');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado ' . $e->getMessage());
            return redirect()->back();
        }

    }

    public function comprasSemValidade(){
        $data = Compra::where('empresa_id', $this->empresa_id)
        ->orderBy('compras.id', 'desc')
        ->get();

        $compras = [];
        foreach($data as $item){
            if($item->verificaValidade()){
                array_push($compras, $item);
            }
        }
        if(sizeof($compras) == 0){
            return response()->json("err", 401);
        }
        return view('compraManual.sem_validade', compact('compras'));
    }

    public function alertaValidade(Request $request){
        $produtos = Produto::where('empresa_id', $this->empresa_id)
        ->where('alerta_vencimento', '>', 0)
        ->get();

        $data = [];
        foreach($produtos as $p){
            $item = ItemCompra::where('produto_id', $p->id)
            ->where('validade', '!=', null)
            ->first();

            $strValidade = strtotime($item->validade);
            $strHoje = strtotime(date('Y-m-d'));
            $dif = $strValidade - $strHoje;
            $dif = $dif/24/60/60;
            $item->dif = (int)$dif;

            if($dif <= $p->alerta_vencimento){
                array_push($data, $item);
            }
        }

        if(sizeof($data) == 0){
            return response()->json("err", 401);
        }
        return view('compraManual.produtos_alerta_validade', compact('data'));

        // return response()->json($data, 200);
    }

    public function alertaEstoque(Request $request){
        $produtos = Produto::where('empresa_id', $this->empresa_id)
        ->where('estoque_minimo', '>', 0)
        ->get();

        $data = [];
        foreach($produtos as $p){
            if($p->estoque){
                if($p->estoque->quantidade <= $p->estoque_minimo){
                    array_push($data, $p);
                }
            }else{
                array_push($data, $p);
            }
        }

        if(sizeof($data) == 0){
            return response()->json("err", 401);
        }
        return view('compraManual.produtos_alerta_estoque', compact('data'));

        return response()->json($data, 200);
    }

    public function imprimirAlertaEstoque(){
        $produtos = Produto::where('empresa_id', $this->empresa_id)
        ->where('estoque_minimo', '>', 0)
        ->get();

        $data = [];
        foreach($produtos as $p){
            if($p->estoque){
                if($p->estoque->quantidade <= $p->estoque_minimo){
                    array_push($data, $p);
                }
            }else{
                array_push($data, $p);
            }
        }

        if(sizeof($data) == 0){
            return response()->json("err", 401);
        }
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();
        $p = view('compraManual/print_estoque')
        ->with('config', $config)
        ->with('data', $data);

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Alerta de estoque.pdf", array("Attachment" => false));

    }

    public function setDadosImportacaoItem(Request $request){

        $item = ItemCompra::findOrFail($request->item_id);
        try{

            $item->nDI = $request->nDI;
            $item->dDI = $request->dDI;
            $item->cidade_desembarque_id = $request->cidade_desembarque_id;
            $item->dDesemb = $request->dDesemb;
            $item->tpViaTransp = $request->tpViaTransp;
            $item->vAFRMM = __replace($request->vAFRMM);
            $item->tpIntermedio = $request->tpIntermedio;
            $item->documento = $request->documento;
            $item->UFTerceiro = $request->UFTerceiro;
            $item->cExportador = $request->cExportador;
            $item->nAdicao = $request->nAdicao;
            $item->cFabricante = $request->cFabricante;

            $item->save();
            session()->flash('mensagem_sucesso', 'Dados definidos para o item!');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();
    }

}
