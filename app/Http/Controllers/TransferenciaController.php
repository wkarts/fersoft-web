<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Filial;
use App\Models\Estoque;
use App\Models\ConfigNota;
use App\Models\Usuario;
use App\Models\NaturezaOperacao;
use App\Models\Transportadora;
use App\Models\Produto;
use App\Models\Transferencia;
use App\Models\ItemTransferencia;
use App\Helpers\StockMove;
use DB;
use App\Prints\ComprovanteTransferencia;
use Dompdf\Dompdf;
use App\Services\TransferenciaService;
//use NFePHP\DA\NFe\Danfe;
use App\Services\CustomDanfe as Danfe;
use NFePHP\DA\NFe\Daevento;

class TransferenciaController extends Controller
{
    protected $empresa_id = null;
    public function __construct(){
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }
            return $next($request);
        });
    }

    public function index(){

        $filiais = Filial::where('empresa_id', $this->empresa_id)
        ->where('status',1)->get();

        if(sizeof($filiais) == 0){
            session()->flash('mensagem_erro', 'É necessário ter ao menos uma filial ativa!');
            return redirect()->back();
        }
        $usuario = Usuario::findOrFail(get_id_user());
        $locaisUsuario = __locaisAtivosUsuario($usuario);

        if(sizeof($locaisUsuario) < 2){
            session()->flash('mensagem_erro', 'É necessário estar vinculado a mais de um local para transferência');
            return redirect()->back();
        }

        return view('transferencias.index', compact('filiais'))
        ->with('title', 'Transferência de estoque');
    }

    public function store(Request $request){
        $entrada = $request->entrada;
        $saida = $request->saida;

        if($entrada == $saida){
            session()->flash('mensagem_erro', 'Selecione corretamente os locais!');
            return redirect()->back();
        }
        // dd($request->all());

        for($i=0; $i<sizeof($request->produto); $i++){

            $produto = $request->produto[$i];
            $quantidade = $request->quantidade[$i];

            $prod = Produto::findOrFail($produto);

            $estoqueAtual = Estoque
            ::where('produto_id', $produto)
            ->when($saida != -1, function ($query) use ($saida) {
                return $query->where('filial_id', $saida);
            })
            ->first();

            if($estoqueAtual == null || $estoqueAtual->quantidade < $quantidade){
                session()->flash('mensagem_erro', 'Estoque insuficiente do produto ' . $prod->nome);
                return redirect()->back();
            }
        }


        try{
            DB::transaction(function () use ($request) {
                $entrada = $request->entrada;
                $saida = $request->saida;

                $entrada = $request->entrada > 0 ? $request->entrada : null;
                $saida = $request->saida > 0 ? $request->saida : null;
                $item = Transferencia::create([
                    'empresa_id' => $this->empresa_id,
                    'filial_saida_id' => $saida,
                    'filial_entrada_id' => $entrada,
                    'observacao' => $request->observacao ?? '',
                    'usuario_id' => get_id_user()
                ]);

                $stockMove = new StockMove();

                for($i=0; $i<sizeof($request->produto); $i++){

                    $produto = $request->produto[$i];
                    $quantidade = __replace($request->quantidade[$i]);

                    $estoqueAtual = Estoque
                        ::where('produto_id', $produto)
                        ->when($saida != -1, function ($query) use ($saida) {
                            return $query->where('filial_id', $saida);
                        })
                        ->first();

                    $p = Produto::findOrFail($produto); // <- aqui é a CORREÇÃO

                    $locais = json_decode($p->locais);
                    array_push($locais, $entrada);

                    if(!in_array($entrada, $locais)){
                        array_push($locais, $entrada);
                    }
                    $p->locais = $locais;
                    $p->save();

                    $stockMove->downStock($produto, $quantidade, $saida);
                    $stockMove->pluStock($produto, $quantidade, -1, $entrada);

                    ItemTransferencia::create([
                        'transferencia_id' => $item->id,
                        'produto_id' => $produto,
                        'quantidade' => $quantidade,
                        'valor_unitario' => $p->valor_compra ?? 0, // preço de custo
                        'sub_total' => ($p->valor_compra ?? 0) * $quantidade,
                    ]);
                }
            });
            session()->flash("mensagem_sucesso", "Transferência realizada!");

        }catch(\Exception $e){
            // echo $e->getMessage();
            // die;
            __saveError($e, $this->empresa_id);
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
        }

        return redirect()->back();

    }

    public function list(){
        $data = Transferencia::where('empresa_id', $this->empresa_id)
        ->orderBy('id', 'desc')
        ->paginate(50);

        return view('transferencias.list', compact('data'))
        ->with('links', 1)
        ->with('title', 'Lista de transferências');
    }

    public function search(Request $request){
        $pesquisa = $request->pesquisa;
        $data = Transferencia::where('transferencias.empresa_id', $this->empresa_id)
        ->select('transferencias.*')
        ->orderBy('id', 'desc')
        ->join('produtos', 'produtos.id', '=', 'transferencias.produto_id')
        ->when($pesquisa, function ($query) use ($pesquisa) {
            return $query->where('produtos.nome', 'like', "%$pesquisa%");
        })
        ->get();

        return view('transferencias.list', compact('data'))
        ->with('pesquisa', $pesquisa)
        ->with('title', 'Lista de transferências');
    }

    public function view($id){
        $item = Transferencia::findOrFail($id);

        $naturezas = NaturezaOperacao::
        where('empresa_id', $this->empresa_id)
        ->get();

        $transportadoras = Transportadora::
        where('empresa_id', $this->empresa_id)
        ->get();

        return view('transferencias.view', compact('item', 'naturezas', 'transportadoras'))
        ->with('title', 'Transferência');
    }

    public function print($id){
        $item = Transferencia::findOrFail($id);

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $p = view('transferencias/print', compact('item', 'config'));
        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Comprovante de Transferencia $id.pdf", array("Attachment" => false));

        // $cupom = new ComprovanteTransferencia($item);
        // $cupom->monta();
        // $pdf = $cupom->render();

        // return response($pdf)
        // ->header('Content-Type', 'application/pdf');

    }

    public function updateFiscal(Request $request, $id){
        $item = Transferencia::findOrFail($id);
        try{

            $item->natureza_id = $request->natureza_id;
            $item->finNFe = $request->finNFe;
            $item->tpNF = $request->tpNF;
            $item->transportadora_id = $request->transportadora_id;
            $item->save();
            session()->flash("mensagem_sucesso", "Dados salvos!");
        }catch(\Exception $e){

            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
        }
        return redirect()->back();
    }

    public function xmlTemp($id)
    {
        $item = Transferencia::findOrFail($id);

        //$this->definirNumeroESerieParaTransferencia($item);
        $this->definirNumeroESerieParaTransferencia($item, false);

        $isFilial = $item->filial_id;

        if ($item->filial_saida_id == null) {
            // Emitente é Matriz
            $emitente = $this->__loadMatriz($item);
            $destinatario = $this->__loadFilial($item);
            $isFilial = false;
        } else {
            // Emitente é Filial
            $emitente = $this->__loadFilial($item);
            $emitente = $this->completarCamposFilialComMatriz($emitente); // << completar os campos da matriz
            $destinatario = $this->__loadMatriz($item);
            $isFilial = true;
        }



        $nfe_service = new TransferenciaService([
            "atualizacao" => date('Y-m-d H:i:s'),
            "tpAmb" => (int)($emitente['ambiente'] ?? 2),
            "razaosocial" => $emitente['razao_social'],
            "siglaUF" => $emitente['UF'],
            "cUF" => $emitente['cUF'],
            "cnpj" => $emitente['cnpj'],
            "schemes" => $emitente['schemes'] ?? config('fiscal.default_schemes'),
            "versao" => $emitente['versao'] ?? "4.00",
            "tokenIBPT" => $emitente['token_ibpt'] ?? '',
            "CSC" => $emitente['csc'] ?? '',
            "CSCid" => $emitente['csc_id'] ?? '',
            "is_filial" => $isFilial,
            "emitente" => $emitente,
            "destinatario" => $destinatario,
        ]);

        $nfe = $nfe_service->gerarNFe($item);

        if (!isset($nfe['erros_xml'])) {
            $xml = $nfe['xml'];
            return response($xml)
                ->header('Content-Type', 'application/xml');
        } else {
            print_r($nfe['erros_xml']);
        }
    }

    public function transmitirNfe(Request $request)
    {
        $item = Transferencia::findOrFail($request->transferencia_id);

        $isFilial = $item->filial_id;

        if ($item->filial_saida_id == null) {
            $emitente = $this->__loadMatriz($item);
            $destinatario = $this->__loadFilial($item);
            $isFilial = false;
        } else {
            $emitente = $this->__loadFilial($item);
            $emitente = $this->completarCamposFilialComMatriz($emitente);
            $destinatario = $this->__loadMatriz($item);
            $isFilial = true;
        }

        $nfe_service = new TransferenciaService([
            "atualizacao" => date('Y-m-d H:i:s'),
            "tpAmb" => (int)($emitente['ambiente'] ?? 2),
            "razaosocial" => $emitente['razao_social'],
            "siglaUF" => $emitente['UF'],
            "cUF" => $emitente['cUF'],
            "cnpj" => $emitente['cnpj'],
            "schemes" => $emitente['schemes'] ?? config('fiscal.default_schemes'),
            "versao" => $emitente['versao'] ?? "4.00",
            "tokenIBPT" => $emitente['token_ibpt'] ?? '',
            "CSC" => $emitente['csc'] ?? '',
            "CSCid" => $emitente['csc_id'] ?? '',
            "is_filial" => $isFilial,
            "emitente" => $emitente,
            "destinatario" => $destinatario,
        ]);

        if ($item->estado == 'novo' || $item->estado == 'rejeitado') {

            // ✅ Só gera novo número oficial no banco agora
            $this->definirNumeroESerieParaTransferencia($item, true);

            $nfe = $nfe_service->gerarNFe($item);

            if (!isset($nfe['erros_xml'])) {

                $signed = $nfe_service->sign($nfe['xml']);
                $resultado = $nfe_service->transmitir($signed, $nfe['chave'], $item->id);

                if (
                    (is_array($resultado) && isset($resultado['cStat']) && in_array($resultado['cStat'], [100, 150, 110]))
                    || (is_string($resultado) && substr($resultado, 0, 4) !== 'Erro')
                ) {
                    // 🔵 APROVADO
                    $item->chave = $nfe['chave'];
                    $item->estado = 'aprovado';
                    $item->data_emissao = date('Y-m-d H:i:s');
                    $item->numero_nfe = $nfe['nNf'];
                    $item->save();

                    // Atualiza o último número utilizado na filial/matriz
                    $this->atualizaUltimoNumeroNfe($item);

                    // Atualiza o último número utilizado na filial/matriz
                    /*
                    if ($item->filial_saida_id) {
                        $filial = Filial::find($item->filial_saida_id);
                        if ($filial && $filial->ultimo_numero_nfe < $item->numero_nfe) {
                            $filial->ultimo_numero_nfe = $item->numero_nfe;
                            $filial->save();
                        }
                    } else {
                        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
                        if ($config && $config->ultimo_numero_nfe < $item->numero_nfe) {
                            $config->ultimo_numero_nfe = $item->numero_nfe;
                            $config->save();
                        }
                    }
                    */

                    importaXmlSieg(safe_file_get_contents(public_path('xml_nfe/' . $nfe['chave'] . '.xml')), $this->empresa_id);

                } else {
                    // 🔴 REJEITADO
                    if (empty($item->signed_xml)) {
                        $item->signed_xml = $signed;
                    }
                    $item->estado = 'rejeitado';
                    $item->chave = $nfe['chave'];
                    $item->save();
                }

                // Sempre responde
                if (is_array($resultado) && isset($resultado['mensagem'])) {
                    echo json_encode($resultado['mensagem']);
                } else {
                    echo json_encode($resultado);
                }

            } else {
                return response()->json($nfe['erros_xml'], 401);
            }
        } else {
            echo json_encode("Apro");
        }
    }

    public function danfeTemp($id)
    {
        $item = Transferencia::findOrFail($id);

        //$this->definirNumeroESerieParaTransferencia($item);
        $this->definirNumeroESerieParaTransferencia($item, false);

        $isFilial = $item->filial_id;

        if ($item->filial_saida_id == null) {
            // Emitente é Matriz
            $emitente = $this->__loadMatriz($item);
            $destinatario = $this->__loadFilial($item);
            $isFilial = false;
        } else {
            // Emitente é Filial
            $emitente = $this->__loadFilial($item);
            $emitente = $this->completarCamposFilialComMatriz($emitente); // << completar os campos da matriz
            $destinatario = $this->__loadMatriz($item);
            $isFilial = true;
        }

        $nfe_service = new TransferenciaService([
            "atualizacao" => date('Y-m-d H:i:s'),
            "tpAmb" => (int)($emitente['ambiente'] ?? 2),
            "razaosocial" => $emitente['razao_social'],
            "siglaUF" => $emitente['UF'],
            "cUF" => $emitente['cUF'],
            "cnpj" => $emitente['cnpj'],
            "schemes" => $emitente['schemes'] ?? config('fiscal.default_schemes'),
            "versao" => $emitente['versao'] ?? "4.00",
            "tokenIBPT" => $emitente['token_ibpt'] ?? '',
            "CSC" => $emitente['csc'] ?? '',
            "CSCid" => $emitente['csc_id'] ?? '',
            "is_filial" => $isFilial,
            "emitente" => $emitente,
            "destinatario" => $destinatario,
        ]);

        $nfe = $nfe_service->gerarNFe($item);

        if (!isset($nfe['erros_xml'])) {
            $xml = $nfe['xml'];

            if (!empty($emitente['logo'])) {
                $logoPath = public_path('logos/') . $emitente['logo'];
                if (file_exists($logoPath)) {
                    $logo = 'data://text/plain;base64,' . base64_encode(safe_file_get_contents($logoPath));
                } else {
                    $logo = null;
                }
            } else {
                $logo = null;
            }

            try {
                $danfe = new Danfe($xml);
                $danfe->setVUnComCasasDec($emitente['casas_decimais'] ?? 2);

                $pdf = $danfe->render($logo);
                header("Content-Disposition: ; filename=DANFE_Temporaria.pdf");
                return response($pdf)
                    ->header('Content-Type', 'application/pdf');
            } catch (InvalidArgumentException $e) {
                echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
            }
        } else {
            print_r($nfe['erros_xml']);
        }
    }

    public function imprimirNfe($id)
    {
        $item = Transferencia::where('id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        if (!valida_objeto($item)) {
            return redirect('/403');
        }

        $isFilial = $item->filial_id;

        if ($item->filial_saida_id == null) {
            // Emitente é Matriz
            $emitente = $this->__loadMatriz($item);
            $destinatario = $this->__loadFilial($item);
            $isFilial = false;
        } else {
            // Emitente é Filial
            $emitente = $this->__loadFilial($item);
            $emitente = $this->completarCamposFilialComMatriz($emitente); // << completar os campos da matriz
            $destinatario = $this->__loadMatriz($item);
            $isFilial = true;
        }

        if (file_exists(public_path('xml_nfe/') . $item->chave . '.xml')) {
            $xml = safe_file_get_contents(public_path('xml_nfe/') . $item->chave . '.xml');

            if (!empty($emitente['logo'])) {
                $logoPath = public_path('logos/') . $emitente['logo'];
                if (file_exists($logoPath)) {
                    $logo = 'data://text/plain;base64,' . base64_encode(safe_file_get_contents($logoPath));
                } else {
                    $logo = null;
                }
            } else {
                $logo = null;
            }

            try {
                $danfe = new Danfe($xml);
                $danfe->setVUnComCasasDec($emitente['casas_decimais'] ?? 2);

                $pdf = $danfe->render($logo);
                header("Content-Disposition: ; filename=DANFE_{$item->numero_nfe}.pdf");
                return response($pdf)->header('Content-Type', 'application/pdf');
            } catch (InvalidArgumentException $e) {
                echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
            }
        } else {
            echo "Arquivo XML não encontrado!!";
        }
    }

    public function corrigirNfe(Request $request)
    {
        $item = Transferencia::findOrFail($request->transferencia_id);

        $isFilial = $item->filial_saida_id;

        if ($item->filial_saida_id == null) {
            // Emitente é Matriz
            $emitente = $this->__loadMatriz($item);
            $destinatario = $this->__loadFilial($item);
            $isFilial = false;
        } else {
            // Emitente é Filial
            $emitente = $this->__loadFilial($item);
            $emitente = $this->completarCamposFilialComMatriz($emitente); // << completar os campos da matriz
            $destinatario = $this->__loadMatriz($item);
            $isFilial = true;
        }

        $nfe_service = new TransferenciaService([
            "atualizacao" => date('Y-m-d H:i:s'),
            "tpAmb" => (int)($emitente['ambiente'] ?? 2),
            "razaosocial" => $emitente['razao_social'],
            "siglaUF" => $emitente['UF'],
            "cUF" => $emitente['cUF'],
            "cnpj" => $emitente['cnpj'],
            "schemes" => $emitente['schemes'] ?? config('fiscal.default_schemes'),
            "versao" => $emitente['versao'] ?? "4.00",
            "tokenIBPT" => $emitente['token_ibpt'] ?? '',
            "CSC" => $emitente['csc'] ?? '',
            "CSCid" => $emitente['csc_id'] ?? '',
            "is_filial" => $isFilial,
            "emitente" => $emitente,
            "destinatario" => $destinatario,
        ]);

        $nfe = $nfe_service->cartaCorrecao($item, $request->correcao);
        echo json_encode($nfe);
    }

    public function cancelarNfe(Request $request)
    {
        $item = Transferencia::findOrFail($request->transferencia_id);

        $isFilial = $item->filial_saida_id;

        if ($item->filial_saida_id == null) {
            $emitente = $this->__loadMatriz($item);
            $destinatario = $this->__loadFilial($item);
            $isFilial = false;
        } else {
            $emitente = $this->__loadFilial($item);
            $destinatario = $this->__loadMatriz($item);
            $isFilial = true;
        }

        $nfe_service = new TransferenciaService([
            "atualizacao" => date('Y-m-d H:i:s'),
            "tpAmb" => (int)($emitente['ambiente'] ?? 2),
            "razaosocial" => $emitente['razao_social'],
            "siglaUF" => $emitente['UF'],
            "cUF" => $emitente['cUF'],
            "cnpj" => $emitente['cnpj'],
            "schemes" => $emitente['schemes'] ?? config('fiscal.default_schemes'),
            "versao" => $emitente['versao'] ?? "4.00",
            "tokenIBPT" => $emitente['token_ibpt'] ?? '',
            "CSC" => $emitente['csc'] ?? '',
            "CSCid" => $emitente['csc_id'] ?? '',
            "is_filial" => $isFilial,
            "emitente" => $emitente,
            "destinatario" => $destinatario,
        ]);

        $nfe = $nfe_service->cancelar($item, $request->justificativa);

        $statusHttp = $nfe['status'] ?? 200;

        if ((isset($nfe['success']) && $nfe['success'] === true) || ($statusHttp >= 200 && $statusHttp < 300)) {
            $pathXml = public_path('xml_nfe_cancelada/' . $item->chave . '.xml');
            if (!file_exists($pathXml)) {
                $pathXml = public_path('xml_nfe_enviada/' . $item->chave . '.xml');
            }

            if (file_exists($pathXml)) {
                importaXmlSieg(safe_file_get_contents($pathXml), $this->empresa_id);
            }

            return response()->json([
                'success' => true,
                'mensagem' => $nfe['data'] ?? 'Cancelamento concluído com sucesso.',
            ], 200);

        } elseif (isset($nfe['erro']) && is_array($nfe['data']) && isset($nfe['data']['retEvento']['infEvento']['cStat']) && $nfe['data']['retEvento']['infEvento']['cStat'] == '573') {
            $pathXml = public_path('xml_nfe_cancelada/' . $item->chave . '.xml');
            if (!file_exists($pathXml)) {
                $pathXml = public_path('xml_nfe_enviada/' . $item->chave . '.xml');
            }

            if (file_exists($pathXml)) {
                importaXmlSieg(safe_file_get_contents($pathXml), $this->empresa_id);

                return response()->json([
                    'success' => true,
                    'mensagem' => 'Evento já processado na SEFAZ (duplicidade 573).',
                ], 200);
            } else {
                return response()->json([
                    'erro' => true,
                    'mensagem' => 'Duplicidade detectada, mas o XML não foi encontrado.',
                ], 404);
            }
        } else {
            return response()->json([
                'erro' => true,
                'mensagem' => is_array($nfe['data']) ? json_encode($nfe['data']) : $nfe['data'],
            ], $nfe['status'] ?? 422);
        }
    }

    public function imprimirCorrecao($id)
    {
        $item = Transferencia::where('id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        if (!$item || $item->sequencia_cce <= 0) {
            echo "<center><h1>Este documento não possui evento de correção!<h1></center>";
            return;
        }

        if (file_exists(public_path('xml_nfe_correcao/') . $item->chave . '.xml')) {
            $xml = safe_file_get_contents(public_path('xml_nfe_correcao/') . $item->chave . '.xml');

            $emitente = $this->__loadMatriz($item);

            if (!empty($emitente['logo'])) {
                $logoPath = public_path('logos/') . $emitente['logo'];
                if (file_exists($logoPath)) {
                    $logo = 'data://text/plain;base64,' . base64_encode(safe_file_get_contents($logoPath));
                } else {
                    $logo = null;
                }
            } else {
                $logo = null;
            }

            $dadosEmitente = [
                'razao' => $emitente['razao_social'],
                'logradouro' => $emitente['logradouro'],
                'numero' => $emitente['numero'],
                'complemento' => $emitente['complemento'],
                'bairro' => $emitente['bairro'],
                'CEP' => $emitente['cep'],
                'municipio' => $emitente['municipio'],
                'UF' => $emitente['UF'],
                'telefone' => $emitente['fone'],
                'email' => $emitente['email'],
            ];

            try {
                $daevento = new Daevento($xml, $dadosEmitente);
                $daevento->debugMode(true);
                $pdf = $daevento->render($logo);

                return response($pdf)->header('Content-Type', 'application/pdf');
            } catch (InvalidArgumentException $e) {
                echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
            }
        } else {
            echo "Arquivo XML não encontrado!!";
        }
    }

    public function imprimirCancela($id)
    {
        $item = Transferencia::where('id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->first();

        if (!$item || $item->estado !== 'cancelado') {
            echo "<center><h1>Este documento não possui evento de cancelamento!<h1></center>";
            return;
        }

        if (file_exists(public_path('xml_nfe_cancelada/') . $item->chave . '.xml')) {
            $xml = safe_file_get_contents(public_path('xml_nfe_cancelada/') . $item->chave . '.xml');

            $emitente = $this->__loadMatriz($item);

            if (!empty($emitente['logo'])) {
                $logoPath = public_path('logos/') . $emitente['logo'];
                if (file_exists($logoPath)) {
                    $logo = 'data://text/plain;base64,' . base64_encode(safe_file_get_contents($logoPath));
                } else {
                    $logo = null;
                }
            } else {
                $logo = null;
            }

            $dadosEmitente = [
                'razao' => $emitente['razao_social'],
                'logradouro' => $emitente['logradouro'],
                'numero' => $emitente['numero'],
                'complemento' => $emitente['complemento'],
                'bairro' => $emitente['bairro'],
                'CEP' => $emitente['cep'],
                'municipio' => $emitente['municipio'],
                'UF' => $emitente['UF'],
                'telefone' => $emitente['fone'],
                'email' => $emitente['email'],
            ];

            try {
                $daevento = new Daevento($xml, $dadosEmitente);
                $daevento->debugMode(true);
                $pdf = $daevento->render($logo);

                return response($pdf)->header('Content-Type', 'application/pdf');
            } catch (InvalidArgumentException $e) {
                echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
            }
        } else {
            echo "Arquivo XML não encontrado!!";
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

    public function atualizarValores($id){
        $transferencia = Transferencia::findOrFail($id);

        foreach($transferencia->itens as $item){
            $produto = $item->produto;
            if($produto){
                $item->valor_unitario = $produto->valor_compra ?? 0;
                $item->sub_total = ($produto->valor_compra ?? 0) * $item->quantidade;
                $item->save();
            }
        }

        return response()->json(['success' => true]);
    }

    public function __getConfigNotaTransferencia($item)
    {
        $config = null;
        $isFilial = null;

        if (!empty($item->filial_saida_id)) {
            $config = Filial::find($item->filial_saida_id);
            $isFilial = true;
        }

        if (!$config && !empty($item->filial_entrada_id)) {
            $config = Filial::find($item->filial_entrada_id);
            $isFilial = true;
        }

        if (!$config) {
            $config = ConfigNota::where('empresa_id', $item->empresa_id)->first();
            $isFilial = false;
        }

        if (!$config || empty($config->UF)) {
            throw new \Exception('Configuração inválida: UF não encontrada para emissão.');
        }

        return [$config, $isFilial];
    }

    public function __getConfigNotaDestino($item)
    {
        if (!empty($item->filial_entrada_id)) {
            $config = Filial::find($item->filial_entrada_id);
            $isFilial = true;
        } else {
            $config = ConfigNota::where('empresa_id', $item->empresa_id)->first();
            $isFilial = false;
        }

        if (!$config || empty($config->UF)) {
            throw new \Exception('Configuração inválida: UF não encontrada para destino.');
        }

        return [$config, $isFilial];
    }
    private function __loadMatriz(Transferencia $item)
    {
        $configMatriz = ConfigNota::where('empresa_id', $item->empresa_id)->firstOrFail();
        $cnpjMatriz = preg_replace('/[^0-9]/', '', $configMatriz->cnpj);

        return [
            "razao_social" => $configMatriz->razao_social,
            "nome_fantasia" => $configMatriz->nome_fantasia ?? '',
            "cnpj" => $cnpjMatriz,
            "ie" => $configMatriz->ie ?? '',
            "logradouro" => $configMatriz->logradouro ?? '',
            "numero" => $configMatriz->numero ?? '',
            "bairro" => $configMatriz->bairro ?? '',
            "municipio" => $configMatriz->municipio ?? '',
            "codMun" => $configMatriz->codMun ?? '',
            "cep" => $configMatriz->cep ?? '',
            "UF" => $configMatriz->UF ?? '',
            "codPais" => $configMatriz->codPais ?? 1058,
            "pais" => $configMatriz->pais ?? 'BRASIL',
            "fone" => $configMatriz->fone ?? '',
            "email" => $configMatriz->email ?? '',
            "complemento" => $configMatriz->complemento ?? '',
            "aut_xml" => $configMatriz->aut_xml ?? '',
            "logo" => $configMatriz->logo ?? '',
            "inscricao_municipal" => $configMatriz->inscricao_municipal ?? '',
            "cUF" => $configMatriz->cUF ?? '35',
            "ambiente" => (int)($configMatriz->ambiente ?? 2),
            "numero_serie_nfe" => $configMatriz->numero_serie_nfe ?? '001',
            "numero_serie_nfce" => $configMatriz->numero_serie_nfce ?? '002',
            "numero_serie_cte" => $configMatriz->numero_serie_cte ?? '003',
            "numero_serie_mdfe" => $configMatriz->numero_serie_mdfe ?? '004',
            "numero_serie_nfse" => $configMatriz->numero_serie_nfse ?? '005',
            "ultimo_numero_nfe" => $configMatriz->ultimo_numero_nfe ?? 0,
            "ultimo_numero_nfce" => $configMatriz->ultimo_numero_nfce ?? 0,
            "ultimo_numero_cte" => $configMatriz->ultimo_numero_cte ?? 0,
            "ultimo_numero_mdfe" => $configMatriz->ultimo_numero_mdfe ?? 0,
            "ultimo_numero_nfse" => $configMatriz->ultimo_numero_nfse ?? 0,
            "schemes" => $configMatriz->schemes ?? config('fiscal.default_schemes'),
            "versao" => $configMatriz->versao ?? "4.00",
            "csc" => $configMatriz->csc ?? '',
            "csc_id" => $configMatriz->csc_id ?? '',
            "token_ibpt" => $configMatriz->token_ibpt ?? '',
            "casas_decimais" => $configMatriz->casas_decimais ?? 2,
            "casas_decimais_qtd" => $configMatriz->casas_decimais_qtd ?? 2,
            "campo_obs_nfe" => $configMatriz->campo_obs_nfe ?? '',
            "campo_obs_pedido" => $configMatriz->campo_obs_pedido ?? '',
            "percentual_lucro_padrao" => $configMatriz->percentual_lucro_padrao ?? 0.00,
            "percentual_max_desconto" => $configMatriz->percentual_max_desconto ?? 0.00,
            "sobrescrita_csonn_consumidor_final" => $configMatriz->sobrescrita_csonn_consumidor_final ?? '',
            "caixa_por_usuario" => $configMatriz->caixa_por_usuario ?? 1,
            "usar_email_proprio" => $configMatriz->usar_email_proprio ?? 0,
            "gerenciar_estoque_produto" => $configMatriz->gerenciar_estoque_produto ?? 0,
            "gerenciar_comissao_usuario_logado" => $configMatriz->gerenciar_comissao_usuario_logado ?? 0,
            "tipo_impressao_danfe" => $configMatriz->tipo_impressao_danfe ?? 1,
        ];
    }

    private function __loadFilial(Transferencia $item)
    {
        $filialId = $item->filial_saida_id ?? $item->filial_entrada_id;
        if (!$filialId) {
            throw new \Exception('Filial não informada na transferência.');
        }

        $configFilial = Filial::findOrFail($filialId);
        $cnpjFilial = preg_replace('/[^0-9]/', '', $configFilial->cnpj);

        return [
            "empresa_id" => $configFilial->empresa_id, // <<<< Adicionado aqui
            "razao_social" => $configFilial->razao_social,
            "nome_fantasia" => $configFilial->nome_fantasia ?? '',
            "cnpj" => $cnpjFilial,
            "ie" => $configFilial->ie ?? '',
            "logradouro" => $configFilial->logradouro ?? '',
            "numero" => $configFilial->numero ?? '',
            "bairro" => $configFilial->bairro ?? '',
            "municipio" => $configFilial->municipio ?? '',
            "codMun" => $configFilial->codMun ?? '',
            "cep" => $configFilial->cep ?? '',
            "UF" => $configFilial->UF ?? '',
            "codPais" => $configFilial->codPais ?? 1058,
            "pais" => $configFilial->pais ?? 'BRASIL',
            "fone" => $configFilial->fone ?? '',
            "email" => $configFilial->email ?? '',
            "complemento" => $configFilial->complemento ?? '',
            "aut_xml" => $configFilial->aut_xml ?? '',
            "logo" => $configFilial->logo ?? '',
            "inscricao_municipal" => $configFilial->inscricao_municipal ?? '',
            "cUF" => $configFilial->cUF ?? '35',
            "ambiente" => (int)($configFilial->ambiente ?? 2),
            "numero_serie_nfe" => $configFilial->numero_serie_nfe ?? '001',
            "numero_serie_nfce" => $configFilial->numero_serie_nfce ?? '002',
            "numero_serie_cte" => $configFilial->numero_serie_cte ?? '003',
            "numero_serie_mdfe" => $configFilial->numero_serie_mdfe ?? '004',
            "ultimo_numero_nfe" => $configFilial->ultimo_numero_nfe ?? 0,
            "ultimo_numero_nfce" => $configFilial->ultimo_numero_nfce ?? 0,
            "ultimo_numero_cte" => $configFilial->ultimo_numero_cte ?? 0,
            "ultimo_numero_mdfe" => $configFilial->ultimo_numero_mdfe ?? 0,
            "schemes" => $configFilial->schemes ?? config('fiscal.default_schemes'),
            "versao" => $configFilial->versao ?? "4.00",
            "csc" => $configFilial->csc ?? '',
            "csc_id" => $configFilial->csc_id ?? '',
            "token_ibpt" => $configFilial->token_ibpt ?? '',
            "casas_decimais" => $configFilial->casas_decimais ?? 2,
            "casas_decimais_qtd" => $configFilial->casas_decimais_qtd ?? 2,
        ];
    }

    private function completarCamposFilialComMatriz(array $filial)
    {
        $matriz = ConfigNota::where('empresa_id', $filial['empresa_id'] ?? auth()->user()->empresa_id)->first();

        if (!$matriz) {
            throw new \Exception('Configuração da matriz não encontrada para completar dados da filial.');
        }

        $filial['campo_obs_nfe'] = $matriz->campo_obs_nfe ?? '';
        $filial['campo_obs_pedido'] = $matriz->campo_obs_pedido ?? '';
        $filial['token_ibpt'] = $matriz->token_ibpt ?? '';
        $filial['schemes'] = $matriz->schemes ?? config('fiscal.default_schemes');
        $filial['versao'] = $matriz->versao ?? '4.00';
        $filial['csc'] = $matriz->csc ?? '';
        $filial['csc_id'] = $matriz->csc_id ?? '';
        $filial['tipo_impressao_danfe'] = $matriz->tipo_impressao_danfe ?? 1;

        return $filial;
    }

    private function definirNumeroESerieParaTransferencia(Transferencia $transferencia, bool $salvar = false)
    {
       // if ($transferencia->numero_nfe && $transferencia->serie) {
       //     // Se já tem número e série, não faz nada
       //     return;
       // }

        if ($transferencia->filial_saida_id) {
            // É filial
            $emitente = Filial::findOrFail($transferencia->filial_saida_id);
            $ultimoNumero = $emitente->ultimo_numero_nfe ?? 0;

            $novoNumero = $ultimoNumero + 1;

            if ($salvar) {
                // Se é oficial (não temporário), já salva o novo número no banco
                $emitente->ultimo_numero_nfe = $novoNumero;
                $emitente->save();
            }

            $transferencia->numero_nfe = $novoNumero;
            $transferencia->serie = $emitente->numero_serie_nfe ?? '1';
        } else {
            // É matriz
            $emitente = ConfigNota::where('empresa_id', $transferencia->empresa_id)->firstOrFail();
            $ultimoNumero = $emitente->ultimo_numero_nfe ?? 0;

            $novoNumero = $ultimoNumero + 1;

            if ($salvar) {
                // Se é oficial (não temporário), já salva o novo número no banco
                $emitente->ultimo_numero_nfe = $novoNumero;
                $emitente->save();
            }

            $transferencia->numero_nfe = $novoNumero;
            $transferencia->serie = $emitente->numero_serie_nfe ?? '1';
        }

        if ($salvar) {
            $transferencia->save();
        }
    }

    private function atualizaUltimoNumeroNfe($transferencia)
    {
        if ($transferencia->filial_saida_id) {
            $filial = Filial::find($transferencia->filial_saida_id);
            if ($filial && $filial->ultimo_numero_nfe < $transferencia->numero_nfe) {
                $filial->ultimo_numero_nfe = $transferencia->numero_nfe;
                $filial->save();
            }
        } else {
            $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
            if ($config && $config->ultimo_numero_nfe < $transferencia->numero_nfe) {
                $config->ultimo_numero_nfe = $transferencia->numero_nfe;
                $config->save();
            }
        }
    }

    public function consultarSituacaoNfe(Request $request)
    {
        try {
            $item = Transferencia::findOrFail($request->transferencia_id);

            if (!$item->chave) {
                return response()->json(['success' => false, 'mensagem' => 'Chave da NFe não encontrada para esta transferência.']);
            }

            $isFilial = $item->filial_saida_id;

            if ($item->filial_saida_id == null) {
                $emitente = $this->__loadMatriz($item);
                $destinatario = $this->__loadFilial($item);
                $isFilial = false;
            } else {
                $emitente = $this->__loadFilial($item);
                $destinatario = $this->__loadMatriz($item);
                $isFilial = true;
            }

            $nfe_service = new TransferenciaService([
                "atualizacao" => date('Y-m-d H:i:s'),
                "tpAmb" => (int)($emitente['ambiente'] ?? 2),
                "razaosocial" => $emitente['razao_social'],
                "siglaUF" => $emitente['UF'],
                "cUF" => $emitente['cUF'],
                "cnpj" => $emitente['cnpj'],
                "schemes" => $emitente['schemes'] ?? config('fiscal.default_schemes'),
                "versao" => $emitente['versao'] ?? "4.00",
                "tokenIBPT" => $emitente['token_ibpt'] ?? '',
                "CSC" => $emitente['csc'] ?? '',
                "CSCid" => $emitente['csc_id'] ?? '',
                "is_filial" => $isFilial,
                "emitente" => $emitente,
                "destinatario" => $destinatario,
            ]);

            $responseConsulta = $nfe_service->consultarSituacao($item);

            return response()->json($responseConsulta);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensagem' => $e->getMessage()]);
        }
    }


}
