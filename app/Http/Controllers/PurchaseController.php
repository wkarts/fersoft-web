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
use App\Support\TransmissionMessageNormalizer;
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

    public function index()
    {
        $this->numeroSequencial();

        // Deixamos as datas apenas para preencher o campo na tela,
        // mas não vamos filtrar a query por elas agora para mostrar os últimos lançamentos
        $dataInicial = date('01/m/Y');
        $dataFinal = date('d/m/Y');

        $usuario = \App\Models\Usuario::find($this->usuario_id);
        $locais_permitidos = json_decode($usuario->locais) ?? [];

        $query = Compra::orderBy('id', 'desc') // Ordena pelo mais recente
        ->where('empresa_id', $this->empresa_id);

        // Lógica de permissão de locais
        $query->where(function($q) use ($locais_permitidos) {
            if (session('user_logged.adm') == 0) {
                $ids_filiais = array_filter($locais_permitidos, function($id) {
                    return $id != "-1" && $id != ""; // CORRIGIDO: removido ";" extra
                });

                if (count($ids_filiais) > 0) {
                    $q->whereIn('filial_id', $ids_filiais);
                    if (in_array("-1", $locais_permitidos)) {
                        $q->orWhereNull('filial_id');
                    }
                } else {
                    if (in_array("-1", $locais_permitidos)) {
                        $q->whereNull('filial_id');
                    } else {
                        // Se o usuário não tiver permissão configurada,
                        // a query morre aqui (segurança)
                        $q->whereRaw("1=0");
                    }
                }
            }
        });

        // Pega os últimos 15 lançamentos como você pediu
        $compras = $query->paginate(15);

        $somaCompraMensal = $this->somaCompraMensal();

        return view('compraManual/listAll')
            ->with('compras', $compras)
            ->with('dataInicial', $dataInicial)
            ->with('dataFinal', $dataFinal)
            ->with('somaCompraMensal', $somaCompraMensal)
            ->with('links', true)
            ->with('title', 'Compras');
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

    public function filtro(Request $request)
    {
        // 1. Pegamos os dados do request SEM valores padrão fixos de data
        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $fornecedor = $request->fornecedor;
        $numero_emissao = $request->numero_emissao;
        $filial_id = $request->filial_id;

        $usuario = \App\Models\Usuario::find($this->usuario_id);
        $locais_permitidos = json_decode($usuario->locais) ?? [];

        // 2. Iniciamos a query com o empresa_id (obrigatório)
        $compras = Compra::select('compras.*')
            ->orderBy('compras.id', 'desc') // Ordena por ID decrescente para ver as últimas
            ->where('compras.empresa_id', $this->empresa_id);

        // 3. Lógica de permissões (ACL)
        $compras->where(function($q) use ($locais_permitidos) {
            if (session('user_logged.adm') == 0) {
                $ids_filiais = array_filter($locais_permitidos, function($id) {
                    return $id != "-1" && $id != "";
                });
                if (count($ids_filiais) > 0) {
                    $q->whereIn('filial_id', $ids_filiais);
                    if (in_array("-1", $locais_permitidos)) {
                        $q->orWhereNull('filial_id');
                    }
                } else {
                    if (in_array("-1", $locais_permitidos)) {
                        $q->whereNull('filial_id');
                    } else {
                        $q->whereRaw("1=0");
                    }
                }
            }
        });

        // 4. FILTRO DE FORNECEDOR (Opcional)
        if($fornecedor){
            $compras->join('fornecedors', 'fornecedors.id' , '=', 'compras.fornecedor_id')
                ->where('fornecedors.razao_social', 'LIKE', "%$fornecedor%");
        }

        // 5. FILTRO DE DATA (Agora só aplica se AMBAS estiverem preenchidas)
        if($dataInicial && $dataFinal){
            $compras->whereBetween('compras.data_emissao', [
                $this->parseDate($dataInicial) . " 00:00:00",
                $this->parseDate($dataFinal) . " 23:59:59"
            ]);
        }

        // 6. FILTRO DE NÚMERO DA NOTA (Nº Emissão Própria)
        if($numero_emissao){
            // Usamos where especificando a tabela para evitar conflito com o join do fornecedor
            $compras->where('compras.numero_emissao', 'LIKE', "%$numero_emissao%");
        }

        // 7. FILTRO DE FILIAL
        if($filial_id){
            if($filial_id == -1) $compras->whereNull('filial_id');
            else $compras->where('filial_id', $filial_id);
        }

        // Paginação com 50 para buscas
        $compras = $compras->paginate(50);

        return view('compraManual/listAll', compact('compras', 'dataInicial', 'dataFinal', 'fornecedor', 'numero_emissao', 'filial_id'))
            ->with('title', 'Filtro Compras');
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
                if ($i->produto->gerenciar_estoque) {
                    $stockMove->downStock(
                        $i->produto->id,
                        $i->quantidade * $i->produto->conversao_unitaria,
                        $compra->filial_id,
                        'compra',   // <--- ADICIONADO: Avisa ao histórico que a origem é uma compra
                        $compra->id // <--- ADICIONADO: Passa o ID para ele puxar o Fornecedor e a NF
                    );
                }
                $i->delete();
            }

            if($compra->delete()){
                session()->flash('mensagem_sucesso', 'Registro e movimentações removidos!');
            }else{
                session()->flash('mensagem_erro', 'Erro ao excluir!');
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
                "schemes"     => config('fiscal.default_schemes'),
                "versao"      => "4.00",
                "tokenIBPT"   => "AAAAAAA",
                "CSC"         => $config->csc,
                "CSCid"       => $config->csc_id,
                "is_filial"   => $isFilial
            ], 55);

            header('Content-type: text/html; charset=UTF-8');
            $natureza = NaturezaOperacao::find($request->natureza);

            // --- Monta XML ---
            $tipoPagamento = $request->tipo_pagamento ?? $compra->tipo_pagamento ?: '01';
            $nfe = $nfe_service->gerarNFe($compra, $natureza, $tipoPagamento);
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
                return response()->json($statusPayload, 200, [], TransmissionMessageNormalizer::jsonOptions());
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
                return response()->json($statusPayload, 200, [], TransmissionMessageNormalizer::jsonOptions());
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
            return response()->json($statusPayload, 401, [], TransmissionMessageNormalizer::jsonOptions());

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
                "schemes" => config('fiscal.default_schemes'),
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

                    return response()->json($resultado, 200, [], TransmissionMessageNormalizer::jsonOptions());
                }

                if ($resultado->isDenegado()) {
                    $compra->chave = $chave;
                    $compra->estado = 'DENEGADO';
                    $compra->numero_emissao = $nNF;
                    $compra->data_emissao = date('Y-m-d H:i:s');
                    $compra->save();

                    $config->ultimo_numero_nfe = $nNF;
                    $config->save();

                    return response()->json($resultado, 200, [], TransmissionMessageNormalizer::jsonOptions());
                }

                $compra->estado = 'REJEITADO';
                $compra->save();

                return response()->json($resultado, $resultado->httpStatus(), [], TransmissionMessageNormalizer::jsonOptions());
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

                return response()->json($resultado, 200, [], TransmissionMessageNormalizer::jsonOptions());
            }

            $compra->estado = 'REJEITADO';
            $compra->save();
            return response()->json($resultado, 401, [], TransmissionMessageNormalizer::jsonOptions());
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
                "schemes"     => config('fiscal.default_schemes'),
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
            "schemes"     => config('fiscal.default_schemes'),
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
                "schemes" => config('fiscal.default_schemes'),
                "versao" => "4.00",
                "tokenIBPT" => "AAAAAAA",
                "CSC" => $config->csc,
                "CSCid" => $config->csc_id,
                "is_filial" => $isFilial
            ], 55);

            header('Content-type: text/html; charset=UTF-8');
            $natureza = NaturezaOperacao::find($request->natureza);

            $tipoPagamento = $request->tipo_pagamento ?? $compra->tipo_pagamento ?: '90';
            $nfe = $nfe_service->gerarNFe($compra, $natureza, $tipoPagamento);
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
                "schemes" => config('fiscal.default_schemes'),
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
            "schemes" => config('fiscal.default_schemes'),
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
            \Log::debug('Mensagem de erro definida na sessão');

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
            "schemes" => config('fiscal.default_schemes'),
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
                ->whereNotNull('validade') // Jeito mais otimizado do Laravel
                ->first();

            // --- TRAVA DE SEGURANÇA ---
            // Se o produto não tiver nenhuma compra com validade, pula para o próximo
            if (!$item) {
                continue;
            }
            // --------------------------

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

    private function parseDate($date){
        if(strpos($date, "/") !== false){
            $d = explode("/", $date);
            return $d[2] . "-" . $d[1] . "-" . $d[0];
        }
        return $date;
    }

    public function validaXmlImportacao(Request $request)
    {
        try {
            $compra = Compra::with('fornecedor', 'itens.produto')->findOrFail($request->compra_id);

            if (!$request->hasFile('file')) {
                return response()->json(['status' => 'error', 'message' => 'Arquivo não enviado.']);
            }

            $xml = simplexml_load_file($request->file('file')->getRealPath());
            if (!$xml || !isset($xml->NFe) || !isset($xml->NFe->infNFe)) {
                return response()->json(['status' => 'error', 'message' => 'XML inválido ou corrompido.']);
            }

            $infNFe = $xml->NFe->infNFe;
            $cnpj_nfe = (string)$infNFe->emit->CNPJ;
            $cnpj_fornecedor = preg_replace('/\D/', '', $compra->fornecedor->cpf_cnpj);

            $flags = [
                'diverge_cnpj' => ($cnpj_nfe !== $cnpj_fornecedor),
                'diverge_tributos' => false,
                'compra_maior' => false,
                'nfe_maior' => false,
            ];

            $qtd_nfe = 0;
            $valor_nfe = (float)$infNFe->total->ICMSTot->vNF;
            $tributos_ok = true;

            // Percorre os itens do XML para validar Qtd e NCM
            foreach ($infNFe->det as $det) {
                $qtd_nfe += (float)$det->prod->qCom;
                $ncm = (string)$det->prod->NCM;

                $achou_igual = false;
                foreach ($compra->itens as $item) {
                    $prod_ncm = $item->produto->NCM ?? '';

                    // Como a balança não grava CFOP/CST, validamos apenas se o NCM do XML bate com o cadastro
                    if ($prod_ncm == $ncm) {
                        $achou_igual = true;
                    }
                }

                if (!$achou_igual && count($compra->itens) > 0) {
                    $tributos_ok = false;
                }
            }

            $flags['diverge_tributos'] = !$tributos_ok;

            $qtd_compra = (float)$compra->itens->sum('quantidade');
            $valor_compra = (float)$compra->valor;

            // Margem de erro técnica de 0.01 para ponto flutuante
            if ($qtd_compra > ($qtd_nfe + 0.01)) {
                $flags['compra_maior'] = true;
            } elseif (($qtd_nfe > $qtd_compra + 0.01) || ($valor_nfe > $valor_compra + 0.01)) {
                $flags['nfe_maior'] = true;
            }

            return response()->json([
                'status' => 'ok',
                'flags' => $flags
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function importarXmlStore(Request $request)
    {
        try {
            $compra = Compra::findOrFail($request->compra_id);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filePath = $file->getRealPath();

                // 1. Carrega o XML
                $xml = simplexml_load_file($filePath);

                if (!$xml || !isset($xml->NFe) || !isset($xml->NFe->infNFe)) {
                    session()->flash('mensagem_erro', 'XML inválido ou com estrutura incorreta!');
                    return redirect()->back();
                }

                $infNFe = $xml->NFe->infNFe;
                if (!$infNFe->attributes()) {
                    session()->flash('mensagem_erro', 'Não foi possível encontrar os atributos no XML!');
                    return redirect()->back();
                }

                // 2. Extrai os dados principais
                $chave = substr((string)$infNFe->attributes()->Id, 3, 44);
                $numero_nota = (string)$infNFe->ide->nNF;

                // Datas de Emissão e Saída do XML
                $dhEmi = (string)($infNFe->ide->dhEmi ?? $infNFe->ide->dEmi ?? '');
                $dataEmissao = $dhEmi ? \Carbon\Carbon::parse($dhEmi)->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');

                $dhSaiEnt = (string)($infNFe->ide->dhSaiEnt ?? $infNFe->ide->dSaiEnt ?? '');
                $dataSaida = $dhSaiEnt
                    ? \Carbon\Carbon::parse($dhSaiEnt)->format('Y-m-d')
                    : \Carbon\Carbon::parse($dataEmissao)->format('Y-m-d');

                // Busca a categoria "Compra de Sucata" (ou similar contendo "sucata")
                $categoria = \App\Models\CategoriaConta::where('empresa_id', $this->empresa_id)
                    ->where('nome', 'LIKE', '%compra%sucata%')
                    ->first();

                if (!$categoria) {
                    $categoria = \App\Models\CategoriaConta::where('empresa_id', $this->empresa_id)
                        ->where('nome', 'LIKE', '%sucata%')
                        ->first();
                }

                $categoriaId = $categoria ? $categoria->id : $compra->categoria_conta_id;

                // 3. Move o arquivo para a pasta de XMLs de Entrada
                $fileName = $chave . '.xml';
                $file->move(public_path('xml_entrada'), $fileName);

                // 4. Atualiza a Compra original (Cabeçalho + Totais Fiscais)
                $compra->chave = $chave;
                $compra->nf = $numero_nota;
                $compra->xml_path = $fileName;
                $compra->estado = 'IMPORTADO';
                $compra->xml_importado = 1;
                $compra->data_emissao = $dataEmissao;
                $compra->data_saida = $dataSaida;
                $compra->categoria_conta_id = $categoriaId;

                // Totais ICMS / IPI / PIS / COFINS
                if (isset($infNFe->total->ICMSTot)) {
                    $icmsTot = $infNFe->total->ICMSTot;
                    $compra->vbc_icms = (float)($icmsTot->vBC ?? 0);
                    $compra->v_icms   = (float)($icmsTot->vICMS ?? 0);
                    $compra->v_ipi    = (float)($icmsTot->vIPI ?? 0);
                    $compra->v_pis    = (float)($icmsTot->vPIS ?? 0);
                    $compra->v_cofins = (float)($icmsTot->vCOFINS ?? 0);
                }

                // Totais IBS / CBS / IS (Reforma Tributária)
                if (isset($infNFe->total->IBSCBSTot)) {
                    $ibsCbsTot = $infNFe->total->IBSCBSTot;
                    $compra->total_bc_ibs_cbs       = isset($ibsCbsTot->vBCIBSCBS) ? (float)$ibsCbsTot->vBCIBSCBS : null;
                    $compra->total_ibs_uf_dif       = isset($ibsCbsTot->gIBS->gIBSUF->vDif) ? (float)$ibsCbsTot->gIBS->gIBSUF->vDif : null;
                    $compra->total_ibs_uf_dev_trib  = isset($ibsCbsTot->gIBS->gIBSUF->vDevTrib) ? (float)$ibsCbsTot->gIBS->gIBSUF->vDevTrib : null;
                    $compra->total_ibs_uf           = isset($ibsCbsTot->gIBS->gIBSUF->vIBSUF) ? (float)$ibsCbsTot->gIBS->gIBSUF->vIBSUF : null;
                    $compra->total_ibs_mun_dif      = isset($ibsCbsTot->gIBS->gIBSMun->vDif) ? (float)$ibsCbsTot->gIBS->gIBSMun->vDif : null;
                    $compra->total_ibs_mun_dev_trib = isset($ibsCbsTot->gIBS->gIBSMun->vDevTrib) ? (float)$ibsCbsTot->gIBS->gIBSMun->vDevTrib : null;
                    $compra->total_ibs_mun          = isset($ibsCbsTot->gIBS->gIBSMun->vIBSMun) ? (float)$ibsCbsTot->gIBS->gIBSMun->vIBSMun : null;
                    $compra->total_ibs              = isset($ibsCbsTot->gIBS->vIBS) ? (float)$ibsCbsTot->gIBS->vIBS : null;
                    $compra->total_cbs_dif          = isset($ibsCbsTot->gCBS->vDif) ? (float)$ibsCbsTot->gCBS->vDif : null;
                    $compra->total_cbs_dev_trib     = isset($ibsCbsTot->gCBS->vDevTrib) ? (float)$ibsCbsTot->gCBS->vDevTrib : null;
                    $compra->total_cbs              = isset($ibsCbsTot->gCBS->vCBS) ? (float)$ibsCbsTot->gCBS->vCBS : null;
                    $compra->total_ibs_mono         = isset($ibsCbsTot->gMono->vIBSMono) ? (float)$ibsCbsTot->gMono->vIBSMono : null;
                    $compra->total_cbs_mono         = isset($ibsCbsTot->gMono->vCBSMono) ? (float)$ibsCbsTot->gMono->vCBSMono : null;
                    $compra->total_ibs_mono_reten   = isset($ibsCbsTot->gMono->vIBSMonoReten) ? (float)$ibsCbsTot->gMono->vIBSMonoReten : null;
                    $compra->total_cbs_mono_reten   = isset($ibsCbsTot->gMono->vCBSMonoReten) ? (float)$ibsCbsTot->gMono->vCBSMonoReten : null;
                    $compra->total_ibs_mono_ret     = isset($ibsCbsTot->gMono->vIBSMonoRet) ? (float)$ibsCbsTot->gMono->vIBSMonoRet : null;
                    $compra->total_cbs_mono_ret     = isset($ibsCbsTot->gMono->vCBSMonoRet) ? (float)$ibsCbsTot->gMono->vCBSMonoRet : null;

                    $totalIbs = $compra->total_ibs ?? 0;
                    $totalCbs = $compra->total_cbs ?? 0;
                    $compra->total_ibs_cbs = ($totalIbs + $totalCbs) > 0 ? ($totalIbs + $totalCbs) : null;
                }

                if (isset($infNFe->total->ISTot->vIS)) {
                    $compra->total_is = (float)$infNFe->total->ISTot->vIS;
                }

                $compra->save();

                // =========================================================================
                // 5. LÓGICA DO CONTA A PAGAR
                // =========================================================================
                $contaPagar = \App\Models\ContaPagar::where('compra_id', $compra->id)->first();

                if ($contaPagar) {
                    $contaPagar->referencia = "NFe: " . $numero_nota;
                    if ($categoriaId) {
                        $contaPagar->categoria_id = $categoriaId;
                    }
                    $contaPagar->save();
                } else {
                    $novaConta = new \App\Models\ContaPagar();
                    $novaConta->compra_id = $compra->id;
                    $novaConta->empresa_id = $compra->empresa_id;
                    $novaConta->fornecedor_id = $compra->fornecedor_id;
                    $novaConta->valor_integral = $compra->valor;
                    $novaConta->data_vencimento = date('Y-m-d');
                    $novaConta->categoria_id = $categoriaId;
                    $novaConta->referencia = "NFe: " . $numero_nota;
                    $novaConta->status = 0;
                    $novaConta->save();
                }

                // =========================================================================
                // 6. LÓGICA DE NOTA COMPLEMENTAR E DEVOLUÇÃO
                // =========================================================================

                // --- GERAÇÃO DE COMPRA PARA NOTA COMPLEMENTAR ---
                if ($request->has('gerar_complementar') && $request->gerar_complementar == 1) {
                    $qtd_nfe = 0;
                    foreach ($xml->NFe->infNFe->det as $det) {
                        $qtd_nfe += (float)$det->prod->qCom;
                    }
                    $qtd_compra = $compra->itens->sum('quantidade');
                    $dif_qtd = $qtd_compra - $qtd_nfe;

                    if ($dif_qtd > 0) {
                        $novaCompra = $compra->replicate();
                        $novaCompra->estado = 'NOVO';
                        $novaCompra->nf = '';
                        $novaCompra->chave = '';
                        $novaCompra->xml_path = '';
                        $novaCompra->xml_importado = 0;
                        $novaCompra->numero_emissao = 0;
                        $novaCompra->numero_sequencial = 0;
                        $novaCompra->observacao = "Compra Complementar Automática (Ref. Compra original #{$compra->id} com peso maior que a NFe importada).";

                        $valor_unitario = $qtd_compra > 0 ? ($compra->valor / $qtd_compra) : 0;
                        $novaCompra->valor = $valor_unitario * $dif_qtd;
                        $novaCompra->save();

                        $primeiroItem = $compra->itens->first();
                        if ($primeiroItem) {
                            $novoItem = $primeiroItem->replicate();
                            $novoItem->compra_id = $novaCompra->id;
                            $novoItem->quantidade = $dif_qtd;
                            $novoItem->save();
                        }
                    }
                }

                // --- SINALIZAÇÃO E CRIAÇÃO AUTOMÁTICA DE NOTA DE DEVOLUÇÃO PARCIAL ---
                if ($request->has('gerar_devolucao') && $request->gerar_devolucao == 1) {
                    $naturezaDev = \App\Models\NaturezaOperacao::where('empresa_id', $this->empresa_id)
                        ->where('natureza', 'LIKE', '%devolu%')
                        ->first();
                    $naturezaId = $naturezaDev ? $naturezaDev->id : $compra->natureza_id;

                    $devolucao = new \App\Models\Devolucao();
                    $devolucao->empresa_id = $compra->empresa_id;
                    $devolucao->fornecedor_id = $compra->fornecedor_id;
                    $devolucao->usuario_id = $this->usuario_id;
                    $devolucao->natureza_id = $naturezaId;
                    $devolucao->filial_id = $compra->filial_id;
                    $devolucao->valor_integral = (float)$xml->NFe->infNFe->total->ICMSTot->vNF;
                    $devolucao->estado = 0; // 0 = NOVO
                    $devolucao->devolucao_parcial = 1; // 1 = Parcial
                    $devolucao->chave_nf_entrada = $chave;
                    $devolucao->nNf = $numero_nota;
                    $devolucao->tipo = 1; // 1 = Saída
                    $devolucao->motivo = "Devolução parcial da NF-e {$numero_nota} ref. {$chave}";
                    $devolucao->observacao = "Devolução parcial da NF-e {$numero_nota} ref. chave {$chave}";
                    $devolucao->valor_devolvido = 0;

                    $devolucao->chave_gerada = '';
                    $devolucao->numero_gerado = 0;
                    $devolucao->vFrete = 0;
                    $devolucao->vDesc = 0;
                    $devolucao->save();

                    $valor_devolvido_total = 0;
                    $idx = 0;

                    foreach ($compra->itens as $item) {
                        $det = $xml->NFe->infNFe->det[$idx] ?? $xml->NFe->infNFe->det[0];

                        $qtd_xml = (float)$det->prod->qCom;
                        $qtd_compra = (float)$item->quantidade;
                        $dif_qtd = $qtd_xml - $qtd_compra;

                        if ($dif_qtd > 0) {
                            $cfopXml = (string)$det->prod->CFOP;
                            $valor_unit = (float)$det->prod->vUnCom;

                            // Extração do CST (3 dígitos: orig + CST) ou CSOSN
                            $cst_xml = '000';
                            $orig_xml = 0;
                            if (isset($det->imposto->ICMS) && count($det->imposto->ICMS->children()) > 0) {
                                $icmsNode = $det->imposto->ICMS->children()[0];
                                $orig_xml = isset($icmsNode->orig) ? (int)$icmsNode->orig : 0;
                                if (isset($icmsNode->CST)) {
                                    $cst_xml = str_pad($orig_xml . (string)$icmsNode->CST, 3, '0', STR_PAD_LEFT);
                                } elseif (isset($icmsNode->CSOSN)) {
                                    $cst_xml = (string)$icmsNode->CSOSN;
                                }
                            }

                            $pisNode = (isset($det->imposto->PIS) && count($det->imposto->PIS->children()) > 0)
                                ? $det->imposto->PIS->children()[0] : null;
                            $cofinsNode = (isset($det->imposto->COFINS) && count($det->imposto->COFINS->children()) > 0)
                                ? $det->imposto->COFINS->children()[0] : null;
                            $ipiNode = isset($det->imposto->IPI->IPITrib)
                                ? $det->imposto->IPI->IPITrib
                                : ($det->imposto->IPI->IPINT ?? null);

                            $itemDev = new \App\Models\ItemDevolucao();
                            $itemDev->devolucao_id = $devolucao->id;
                            $itemDev->cod = $item->produto->id;
                            $itemDev->nome = $item->produto->nome;
                            $itemDev->ncm = (string)$det->prod->NCM;
                            $itemDev->cfop = $this->getCfopDevolucao($cfopXml);
                            $itemDev->codBarras = $item->produto->codBarras ?? 'SEM GTIN';
                            $itemDev->valor_unit = $valor_unit;
                            $itemDev->quantidade = $dif_qtd;
                            $itemDev->item_parcial = 1;
                            $itemDev->unidade_medida = (string)$det->prod->uCom;

                            $itemDev->cst_csosn = $cst_xml;
                            $itemDev->cst_pis = $pisNode && isset($pisNode->CST) ? (string)$pisNode->CST : '99';
                            $itemDev->cst_cofins = $cofinsNode && isset($cofinsNode->CST) ? (string)$cofinsNode->CST : '99';
                            $itemDev->cst_ipi = $ipiNode && isset($ipiNode->CST) ? (string)$ipiNode->CST : '99';

                            $itemDev->perc_icms = isset($icmsNode->pICMS) ? (float)$icmsNode->pICMS : 0;
                            $itemDev->perc_pis = $pisNode && isset($pisNode->pPIS) ? (float)$pisNode->pPIS : 0;
                            $itemDev->perc_cofins = $cofinsNode && isset($cofinsNode->pCOFINS) ? (float)$cofinsNode->pCOFINS : 0;
                            $itemDev->perc_ipi = $ipiNode && isset($ipiNode->pIPI) ? (float)$ipiNode->pIPI : 0;
                            $itemDev->pRedBC = 0; $itemDev->modBCST = 0;
                            $itemDev->vBCST = 0; $itemDev->pICMSST = 0; $itemDev->vICMSST = 0;
                            $itemDev->pMVAST = 0; $itemDev->orig = $orig_xml; $itemDev->pST = 0;
                            $itemDev->vICMSSubstituto = 0; $itemDev->vICMSSTRet = 0;
                            $itemDev->vbc_manual = 0; $itemDev->vicms_manual = 0; $itemDev->vpis_manual = 0;
                            $itemDev->vcofins_manual = 0; $itemDev->vipi_manual = 0;

                            $itemDev->save();
                            $valor_devolvido_total += ($dif_qtd * $valor_unit);
                        }
                        $idx++;
                    }

                    $devolucao->valor_devolvido = $valor_devolvido_total;
                    $devolucao->save();

                    $compra->observacao = trim($compra->observacao . " [SINALIZADO DEVOLUÇÃO PARCIAL]");
                    $compra->save();
                }

                // =========================================================================
                // 7. ATUALIZAÇÃO COMPLETA DE TRIBUTAÇÃO NOS ITENS DA COMPRA (SPED FISCAL)
                // =========================================================================
                $mapaCfop = [
                    '5102' => '1102',
                    '6102' => '2102',
                    '5949' => '1102',
                    '5502' => '1501',
                    '6502' => '2501'
                ];

                $idx = 0;
                foreach ($compra->itens as $item) {
                    $det = $xml->NFe->infNFe->det[$idx] ?? $xml->NFe->infNFe->det[0];
                    $cfop_xml = (string)$det->prod->CFOP;

                    // Conversão de CFOP
                    if (array_key_exists($cfop_xml, $mapaCfop)) {
                        $cfop_convertido = $mapaCfop[$cfop_xml];
                    } else {
                        if (substr($cfop_xml, 0, 1) == '5') {
                            $cfop_convertido = $item->produto->CFOP_entrada_estadual;
                        } else {
                            $cfop_convertido = $item->produto->CFOP_entrada_inter_estadual;
                        }
                    }
                    $item->cfop_entrada = $cfop_convertido ?: $cfop_xml;

                    // --- ICMS (CST com 3 dígitos: orig + CST, ex: 051) ---
                    if (isset($det->imposto->ICMS) && count($det->imposto->ICMS->children()) > 0) {
                        $icmsNode = $det->imposto->ICMS->children()[0];
                        $orig = isset($icmsNode->orig) ? (string)$icmsNode->orig : '0';

                        if (isset($icmsNode->CST)) {
                            $item->cst_icms = str_pad($orig . (string)$icmsNode->CST, 3, '0', STR_PAD_LEFT);
                        } elseif (isset($icmsNode->CSOSN)) {
                            $item->cst_icms = (string)$icmsNode->CSOSN;
                        }

                        $item->vbc_icms = isset($icmsNode->vBC) ? (float)$icmsNode->vBC : 0;
                        $item->p_icms   = isset($icmsNode->pICMS) ? (float)$icmsNode->pICMS : 0;
                        $item->v_icms   = isset($icmsNode->vICMS) ? (float)$icmsNode->vICMS : 0;
                    }

                    // --- IPI ---
                    if (isset($det->imposto->IPI)) {
                        $ipiNode = $det->imposto->IPI->IPITrib ?? $det->imposto->IPI->IPINT ?? null;
                        if ($ipiNode) {
                            $item->cst_ipi = isset($ipiNode->CST) ? (string)$ipiNode->CST : null;
                            $item->vbc_ipi = isset($ipiNode->vBC) ? (float)$ipiNode->vBC : 0;
                            $item->p_ipi   = isset($ipiNode->pIPI) ? (float)$ipiNode->pIPI : 0;
                            $item->v_ipi   = isset($ipiNode->vIPI) ? (float)$ipiNode->vIPI : 0;
                        }
                    }

                    // --- PIS ---
                    if (isset($det->imposto->PIS) && count($det->imposto->PIS->children()) > 0) {
                        $pisNode = $det->imposto->PIS->children()[0];
                        $item->cst_pis = isset($pisNode->CST) ? (string)$pisNode->CST : null;
                        $item->vbc_pis = isset($pisNode->vBC) ? (float)$pisNode->vBC : 0;
                        $item->p_pis   = isset($pisNode->pPIS) ? (float)$pisNode->pPIS : 0;
                        $item->v_pis   = isset($pisNode->vPIS) ? (float)$pisNode->vPIS : 0;
                    }

                    // --- COFINS ---
                    if (isset($det->imposto->COFINS) && count($det->imposto->COFINS->children()) > 0) {
                        $cofinsNode = $det->imposto->COFINS->children()[0];
                        $item->cst_cofins = isset($cofinsNode->CST) ? (string)$cofinsNode->CST : null;
                        $item->vbc_cofins = isset($cofinsNode->vBC) ? (float)$cofinsNode->vBC : 0;
                        $item->p_cofins   = isset($cofinsNode->pCOFINS) ? (float)$cofinsNode->pCOFINS : 0;
                        $item->v_cofins   = isset($cofinsNode->vCOFINS) ? (float)$cofinsNode->vCOFINS : 0;
                    }

                    // --- IS (Imposto Seletivo) ---
                    if (isset($det->imposto->IS)) {
                        $isNode = $det->imposto->IS;
                        $item->cst_is        = isset($isNode->CSTIS) ? (string)$isNode->CSTIS : null;
                        $item->is_bc         = isset($isNode->vBCIS) ? (float)$isNode->vBCIS : null;
                        $item->is_aliq       = isset($isNode->pIS) ? (float)$isNode->pIS : null;
                        $item->is_aliq_espec = isset($isNode->pISEspec) ? (float)$isNode->pISEspec : null;
                        $item->is_und_trib   = isset($isNode->uTrib) ? (string)$isNode->uTrib : null;
                        $item->is_qtd_trib   = isset($isNode->qTrib) ? (float)$isNode->qTrib : null;
                        $item->is_valor      = isset($isNode->vIS) ? (float)$isNode->vIS : null;
                    }

                    // --- IBS / CBS (Reforma Tributária) ---
                    if (isset($det->imposto->IBSCBS)) {
                        $ibscbs = $det->imposto->IBSCBS;
                        $item->cst_ibs_cbs        = isset($ibscbs->CST) ? (string)$ibscbs->CST : null;
                        $item->class_trib_ibs_cbs = isset($ibscbs->cClassTrib) ? (string)$ibscbs->cClassTrib : null;

                        if (isset($ibscbs->gIBSCBS)) {
                            $g = $ibscbs->gIBSCBS;
                            $item->bc_ibs_cbs = isset($g->vBC) ? (float)$g->vBC : null;

                            // IBS UF
                            if (isset($g->gIBSUF)) {
                                $item->aliq_ibs_uf              = isset($g->gIBSUF->pIBSUF) ? (float)$g->gIBSUF->pIBSUF : null;
                                $item->valor_ibs_uf             = isset($g->gIBSUF->vIBSUF) ? (float)$g->gIBSUF->vIBSUF : null;
                                $item->aliq_efet_ibs_uf         = isset($g->gIBSUF->gRed->pAliqEfet) ? (float)$g->gIBSUF->gRed->pAliqEfet : null;
                                $item->perc_dif_ibs_uf          = isset($g->gIBSUF->gDif->pDif) ? (float)$g->gIBSUF->gDif->pDif : null;
                                $item->valor_dif_ibs_uf         = isset($g->gIBSUF->gDif->vDif) ? (float)$g->gIBSUF->gDif->vDif : null;
                                $item->valor_dif_ibs_uf_devtrib = isset($g->gIBSUF->gDevTrib->vDevTrib) ? (float)$g->gIBSUF->gDevTrib->vDevTrib : null;
                            }

                            // IBS Município
                            if (isset($g->gIBSMun)) {
                                $item->aliq_ibs_mun           = isset($g->gIBSMun->pIBSMun) ? (float)$g->gIBSMun->pIBSMun : null;
                                $item->valor_ibs_mun          = isset($g->gIBSMun->vIBSMun) ? (float)$g->gIBSMun->vIBSMun : null;
                                $item->aliq_efet_ibs_mun      = isset($g->gIBSMun->gRed->pAliqEfet) ? (float)$g->gIBSMun->gRed->pAliqEfet : null;
                                $item->perc_dif_ibs_mun       = isset($g->gIBSMun->gDif->pDif) ? (float)$g->gIBSMun->gDif->pDif : null;
                                $item->valor_dif_ibs_mun      = isset($g->gIBSMun->gDif->vDif) ? (float)$g->gIBSMun->gDif->vDif : null;
                                $item->valor_dif_ibs_mun_trib = isset($g->gIBSMun->gDevTrib->vDevTrib) ? (float)$g->gIBSMun->gDevTrib->vDevTrib : null;
                            }

                            $item->valor_ibs = isset($g->vIBS)
                                ? (float)$g->vIBS
                                : (($item->valor_ibs_uf ?? 0) + ($item->valor_ibs_mun ?? 0));

                            // CBS
                            if (isset($g->gCBS)) {
                                $item->aliq_cbs              = isset($g->gCBS->pCBS) ? (float)$g->gCBS->pCBS : null;
                                $item->valor_cbs             = isset($g->gCBS->vCBS) ? (float)$g->gCBS->vCBS : null;
                                $item->aliq_efet_cbs         = isset($g->gCBS->gRed->pAliqEfet) ? (float)$g->gCBS->gRed->pAliqEfet : null;
                                $item->perc_dif_cbs          = isset($g->gCBS->gDif->pDif) ? (float)$g->gCBS->gDif->pDif : null;
                                $item->valor_dif_cbs         = isset($g->gCBS->gDif->vDif) ? (float)$g->gCBS->gDif->vDif : null;
                                $item->valor_dif_cbs_devtrib = isset($g->gCBS->gDevTrib->vDevTrib) ? (float)$g->gCBS->gDevTrib->vDevTrib : null;
                            }
                        }
                    }

                    $item->save();
                    $idx++;
                }

                // =========================================================================
                // 8. FINALIZAÇÃO E ALERTAS
                // =========================================================================
                if ($request->has('gerar_devolucao') && $request->gerar_devolucao == 1) {
                    $pastaDestino = public_path('xml_devolucao_entrada');
                    if (!file_exists($pastaDestino)) {
                        mkdir($pastaDestino, 0777, true);
                    }
                    if (file_exists(public_path('xml_entrada/' . $chave . '.xml'))) {
                        copy(public_path('xml_entrada/' . $chave . '.xml'), $pastaDestino . '/' . $chave . '.xml');
                    }

                    session()->flash('alerta_devolucao', "XML importado!\n\nVá no menu Vendas -> Devoluções e faça a emissão da NF-e de devolução parcial.");
                } else {
                    session()->flash('mensagem_sucesso', 'XML importado, mapeado e validado com sucesso!');
                }

            } else {
                session()->flash('mensagem_erro', 'Nenhum arquivo enviado.');
            }
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao importar XML: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    private function getCfopDevolucao($cfop)
    {
        $map = [
            '5101' => '5202', '5102' => '5202',
            '6101' => '6202', '6102' => '6202',
            '6502' => '6503', '5502' => '5503',
            '1102' => '5202', '2102' => '6202',
            '1556' => '5556', '2556' => '6556'
        ];

        if (array_key_exists($cfop, $map)) {
            return $map[$cfop];
        }

        // Regra geral de fallback: se começou com 1 vira 5, se começou com 2 vira 6
        if (substr($cfop, 0, 1) == '1') return '5' . substr($cfop, 1);
        if (substr($cfop, 0, 1) == '2') return '6' . substr($cfop, 1);
        if (substr($cfop, 0, 1) == '5') return '5202';
        if (substr($cfop, 0, 1) == '6') return '6202';

        return $cfop;
    }

    public function gerarDevolucaoManual($id)
    {
        try {
            $compra = Compra::with('itens.produto')->findOrFail($id);

            $naturezaDev = \App\Models\NaturezaOperacao::where('empresa_id', $this->empresa_id)
                ->where('natureza', 'LIKE', '%devolu%')
                ->first();
            $naturezaId = $naturezaDev ? $naturezaDev->id : $compra->natureza_id;

            $devolucao = new \App\Models\Devolucao();
            $devolucao->empresa_id = $compra->empresa_id;
            $devolucao->fornecedor_id = $compra->fornecedor_id;
            $devolucao->usuario_id = $this->usuario_id;
            $devolucao->natureza_id = $naturezaId;
            $devolucao->filial_id = $compra->filial_id;
            $devolucao->valor_integral = $compra->valor;
            $devolucao->estado = 0; // NOVO
            $devolucao->devolucao_parcial = 0; // Total
            $devolucao->chave_nf_entrada = $compra->chave ?? '';
            $devolucao->nNf = $compra->numero_emissao ?: ($compra->nf ?: 0);
            $devolucao->tipo = 1;
            $devolucao->motivo = "Devolução Total de Compra ref. NFe " . $devolucao->nNf;
            $devolucao->observacao = "Devolução Total da NF-e {$devolucao->nNf} ref. chave {$devolucao->chave_nf_entrada}";
            $devolucao->valor_devolvido = $compra->valor;

            $devolucao->chave_gerada = '';
            $devolucao->numero_gerado = 0;
            $devolucao->vFrete = 0;
            $devolucao->vDesc = 0;
            $devolucao->save();

            // Copia o XML da compra para a pasta de devoluções
            $chave_xml = $compra->chave;
            if ($chave_xml != "") {
                $pastaDestino = public_path('xml_devolucao_entrada');
                if (!file_exists($pastaDestino)) {
                    mkdir($pastaDestino, 0777, true);
                }

                $path1 = public_path('xml_entrada/' . $chave_xml . '.xml');
                $path2 = public_path('xml_entrada_emitida/' . $chave_xml . '.xml');

                if (file_exists($path1)) {
                    copy($path1, $pastaDestino . '/' . $chave_xml . '.xml');
                } elseif (file_exists($path2)) {
                    copy($path2, $pastaDestino . '/' . $chave_xml . '.xml');
                }
            }

            foreach ($compra->itens as $item) {
                $itemDev = new \App\Models\ItemDevolucao();
                $itemDev->devolucao_id = $devolucao->id;
                $itemDev->cod = $item->produto->id;
                $itemDev->nome = $item->produto->nome;
                $itemDev->ncm = $item->produto->NCM ?? '';

                // Traduz o CFOP da Compra para o de Devolução
                $itemDev->cfop = $this->getCfopDevolucao($item->cfop_entrada);

                $itemDev->codBarras = $item->produto->codBarras ?? 'SEM GTIN';
                $itemDev->valor_unit = $item->valor_unitario;
                $itemDev->quantidade = $item->quantidade;
                $itemDev->item_parcial = 0;
                $itemDev->unidade_medida = $item->unidade_compra;

                $itemDev->cst_csosn = $item->cst_icms ?? '000';
                $itemDev->cst_pis = $item->cst_pis ?? '99';
                $itemDev->cst_cofins = $item->cst_cofins ?? '99';
                $itemDev->cst_ipi = $item->cst_ipi ?? '99';

                $itemDev->perc_icms = 0; $itemDev->perc_pis = 0; $itemDev->perc_cofins = 0;
                $itemDev->perc_ipi = 0; $itemDev->pRedBC = 0; $itemDev->modBCST = 0;
                $itemDev->vBCST = 0; $itemDev->pICMSST = 0; $itemDev->vICMSST = 0;
                $itemDev->pMVAST = 0; $itemDev->orig = 0; $itemDev->pST = 0;
                $itemDev->vICMSSubstituto = 0; $itemDev->vICMSSTRet = 0;
                $itemDev->vbc_manual = 0; $itemDev->vicms_manual = 0; $itemDev->vpis_manual = 0;
                $itemDev->vcofins_manual = 0; $itemDev->vipi_manual = 0;
                $itemDev->save();
            }

            session()->flash('alerta_devolucao', "Espelho de devolução gerado!\n\nVá no menu Vendas -> Devoluções e faça a emissão da NF-e de devolução total.");
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao gerar devolução: ' . $e->getMessage());
        }
        return redirect()->back();
    }
}
