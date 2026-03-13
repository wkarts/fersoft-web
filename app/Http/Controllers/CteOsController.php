<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CteOs;
use App\Models\Certificado;
use App\Models\NaturezaOperacao;
use App\Models\Veiculo;
use App\Models\ConfigNota;
use App\Models\Cliente;
use App\Models\Cidade;
use App\Models\EscritorioContabil;
use App\Services\CTeOsService;
use NFePHP\DA\CTe\DacteOS;
use NFePHP\DA\CTe\Daevento;
use Mail;

class CteOsController extends Controller
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
        $ctes = CteOs::
        // where('estado', 'NOVO')
        where('empresa_id', $this->empresa_id)
        ->paginate(10);

        $menos30 = $this->menos30Dias();
        $date = date('d/m/Y');

        $certificado = Certificado::
        where('empresa_id', $this->empresa_id)
        ->first();

        $estado = 'TODOS';

        return view("cte_os/index")
        ->with('ctes', $ctes)
        ->with('estado', $estado)
        ->with('links', true)
        ->with('dataInicial', $menos30)
        ->with('certificado', $certificado)
        ->with('dataFinal', $date)
        ->with('title', "Lista de CTe Os");

    }

    public function filtro(Request $request){

        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $estado = $request->estado;
        $ctes = null;

        $certificado = Certificado::
        where('empresa_id', $this->empresa_id)
        ->first();

        if(isset($dataInicial) && isset($dataFinal)){
            $ctes = CteOs::filtroData(
                $this->parseDate($dataInicial),
                $this->parseDate($dataFinal, true),
                $estado
            );
        }else{
            $ctes = CteOs::filtroEstado(
                $estado
            );
        }

        return view("cte_os/index")
        ->with('ctes', $ctes)
        ->with('certificado', $certificado)
        ->with('dataInicial', $dataInicial)
        ->with('dataFinal', $dataFinal)
        ->with('estado', $estado)
        ->with('title', "Filtro de Cte Os");
    }

    private function menos30Dias(){
        return date('d/m/Y', strtotime("-30 days",strtotime(str_replace("/", "-",
            date('Y-m-d')))));
    }

    private function parseDate($date, $plusDay = false){
        if($plusDay == false)
            return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
        else
            return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
    }

    public function nova(){
        $lastCte = CteOs::lastCTe();

        $tiposTomador = CteOs::tiposTomador();
        $naturezas = NaturezaOperacao::
        where('empresa_id', $this->empresa_id)
        ->get();

        $modals = CteOs::modals();
        $veiculos = Veiculo::
        where('empresa_id', $this->empresa_id)
        ->get();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $clienteCadastrado = Cliente::
        where('empresa_id', $this->empresa_id)
        ->first();

        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->orderBy('razao_social')
        ->get();

        foreach($clientes as $c){
            $c->cidade;
        }
        $cidades = Cidade::all();
        if(count($naturezas) == 0 || count($veiculos) == 0 || $config == null || $clienteCadastrado == null){
            return view("cte_os/erro")
            ->with('veiculos', $veiculos)
            ->with('naturezas', $naturezas)
            ->with('config', $config)
            ->with('clienteCadastrado', $clienteCadastrado)
            ->with('title', "Validação para Emitir");

        }else{
            return view("cte_os/register")
            ->with('naturezas', $naturezas)
            ->with('tiposTomador', $tiposTomador)
            ->with('modals', $modals)
            ->with('veiculos', $veiculos)
            ->with('clientes', $clientes)
            ->with('cidades', $cidades)
            ->with('config', $config)
            ->with('lastCte', $lastCte)
            ->with('title', "Nova CTe Os");
        }
    }

    public function edit($id){

        $cte = CteOs::findOrFail($id);
        $lastCte = CteOs::lastCTe();

        $tiposTomador = CteOs::tiposTomador();
        $naturezas = NaturezaOperacao::
        where('empresa_id', $this->empresa_id)
        ->get();

        $modals = CteOs::modals();
        $veiculos = Veiculo::
        where('empresa_id', $this->empresa_id)
        ->get();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $clienteCadastrado = Cliente::
        where('empresa_id', $this->empresa_id)
        ->first();

        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->orderBy('razao_social')
        ->get();

        foreach($clientes as $c){
            $c->cidade;
        }
        $cidades = Cidade::all();
        if(count($naturezas) == 0 || count($veiculos) == 0 || $config == null || $clienteCadastrado == null){
            return view("cte_os/erro")
            ->with('veiculos', $veiculos)
            ->with('naturezas', $naturezas)
            ->with('config', $config)
            ->with('clienteCadastrado', $clienteCadastrado)
            ->with('title', "Validação para Emitir");

        }else{
            return view("cte_os/register")
            ->with('naturezas', $naturezas)
            ->with('tiposTomador', $tiposTomador)
            ->with('modals', $modals)
            ->with('veiculos', $veiculos)
            ->with('cte', $cte)
            ->with('clientes', $clientes)
            ->with('cidades', $cidades)
            ->with('config', $config)
            ->with('lastCte', $lastCte)
            ->with('title', "Editar CTe Os");
        }
    }

    public function salvar(Request $request){
        $cte = $request->data;

        $municipio_envio = (int) explode("-", $cte['municipio_envio'])[0];
        $municipio_fim = (int) explode("-", $cte['municipio_fim'])[0];
        $municipio_inicio = (int) explode("-", $cte['municipio_inicio'])[0];

        $result = CteOs::create([
            'tomador_id' => $cte['tomador_id'],
            'emitente_id' => $cte['emitente_id'],
            'usuario_id' => get_id_user(),
            'natureza_id' => $cte['natureza'],
            'tomador' => $cte['tomador'],
            'municipio_envio' => $municipio_envio,
            'municipio_inicio' => $municipio_inicio,
            'municipio_fim' => $municipio_fim,
            'observacao' => $cte['obs'] ?? '',
            'numero_emissao' => 0,
            'sequencia_cce' => 0,
            'chave' => '',
            'estado' => 'NOVO',

            'valor_transporte' => str_replace(",", ".", $cte['valor_transporte']),
            'valor_receber' => str_replace(",", ".", $cte['valor_receber']),
            'quantidade_carga' => str_replace(",", ".", $cte['quantidade_carga']),
            'descricao_servico' => $cte['descricao_servico'],
            'modal' => $cte['modal'],
            'veiculo_id' => $cte['veiculo_id'],
            'empresa_id' => $this->empresa_id,
            'cst' => $cte['cst'],
            'perc_icms' => $cte['perc_icms'] ?? 0,
            'data_viagem' => $cte['data_viagem'],
            'horario_viagem' => $cte['horario_viagem'],

        ]);

        echo json_encode($result);
    }

    public function delete($id){
        $cte = CteOs::
        where('id', $id)
        ->first();
        if(valida_objeto($cte)){
            if($cte->delete()){
                session()->flash('mensagem_sucesso', 'CTe Os removida!');
            }else{
                session()->flash('mensagem_erro', 'Erro!');
            }
            return redirect('cteos');
        }else{
            return redirect('/403');
        }
    }

    public function xmlTemp($id){
        $cteEmit = CteOs::
        where('id', $id)
        ->where('empresa_id', $this->empresa_id)
        ->first();

        if(!$cteEmit){
            return redirect('/403');
        }

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        $cte_service = new CTeOsService([
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb" => (int)$config->ambiente,
            "razaosocial" => $config->razao_social,
            "siglaUF" => $config->UF,
            "cnpj" => $cnpj,
            "schemes" => "PL_CTe_400",
            "versao" => '4.00',
            "proxyConf" => [
                "proxyIp" => "",
                "proxyPort" => "",
                "proxyUser" => "",
                "proxyPass" => ""
            ]
        ], '57');


        $cte = $cte_service->gerarCTe($cteEmit);
        if(!isset($cte['erros_xml'])){
            $signed = $cte_service->sign($cte['xml']);

            $xml = $signed;
            return response($xml)
            ->header('Content-Type', 'application/xml');
        }else{
            foreach($cte['erros_xml'] as $err){
                echo $err;
            }
        }
    }

    public function enviar(Request $request){

        $cteEmit = CteOs::
        where('id', $request->id)
        ->where('empresa_id', $this->empresa_id)
        ->first();

        if(!$cteEmit){
            return response()->json('Não permitido', 403);
        }

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        $cte_service = new CTeOsService([
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb" => (int)$config->ambiente,
            "razaosocial" => $config->razao_social,
            "siglaUF" => $config->UF,
            "cnpj" => $cnpj,
            "schemes" => "PL_CTe_400",
            "versao" => '4.00',
            "proxyConf" => [
                "proxyIp" => "",
                "proxyPort" => "",
                "proxyUser" => "",
                "proxyPass" => ""
            ]
        ], '67');

        if($cteEmit->estado == 'REJEITADO' || $cteEmit->estado == 'NOVO'){

            $cte = $cte_service->gerarCTe($cteEmit);
            if(!isset($cte['erros_xml'])){
                $signed = $cte_service->sign($cte['xml']);

                $resultado = $cte_service->transmitir($signed, $cte['chave']);

                if(substr($resultado, 0, 4) != 'Erro'){
                    $cteEmit->chave = $cte['chave'];
                    $cteEmit->estado = 'APROVADO';
                    $cteEmit->data_emissao = date('Y-m-d H:i:s');

                    $cteEmit->numero_emissao = $cte['nCte'];
                    $this->enviarEmailAutomatico($cteEmit);

                    $cteEmit->save();
                }else{
                    $cteEmit->estado = 'REJEITADO';
                    $cteEmit->save();
                }
                echo json_encode($resultado);
            }else{
                return response()->json($cte['erros_xml'], 401);
            }
        }else{
            echo json_encode("Apro");
        }

    }

    public function dacteTemp($id){
        $cteEmit = CteOs::
        where('id', $id)
        ->where('empresa_id', $this->empresa_id)
        ->first();

        if(!$cteEmit){
            return redirect('/403');
        }

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        $cte_service = new CTeOsService([
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb" => (int)$config->ambiente,
            "razaosocial" => $config->razao_social,
            "siglaUF" => $config->UF,
            "cnpj" => $cnpj,
            "schemes" => "PL_CTe_400",
            "versao" => '4.00',
            "proxyConf" => [
                "proxyIp" => "",
                "proxyPort" => "",
                "proxyUser" => "",
                "proxyPass" => ""
            ]
        ], '67');

        $cte = $cte_service->gerarCTe($cteEmit);
        if(!isset($cte['erros_xml'])){
            $xml = $cte['xml'];
            $dacte = new DacteOS($xml);
            $dacte->debugMode(true);
            $dacte->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webenf.com.br');
                    // $dacte->monta();

            $pdf = $dacte->render(null);
            header('Content-Type: application/pdf');
            return response($pdf)
            ->header('Content-Type', 'application/pdf');
        }else{
            foreach($cte['erros_xml'] as $err){
                echo $err;
            }
        }
    }

    public function cancelar(Request $request){

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        $cte_service = new CTeOsService([
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb" => (int)$config->ambiente,
            "razaosocial" => $config->razao_social,
            "siglaUF" => $config->UF,
            "cnpj" => $cnpj,
            "schemes" => "PL_CTe_400",
            "versao" => '4.00',
            "proxyConf" => [
                "proxyIp" => "",
                "proxyPort" => "",
                "proxyUser" => "",
                "proxyPass" => ""
            ]
        ], '67');

        $cte = $cte_service->cancelar($request->id, $request->justificativa);

        if(isset($cte['erro'])){
            return response()->json($cte['mensagem'], 401);
        }
        $error = json_decode($cte)->infEvento;
        if($error->cStat == '101' || $error->cStat == '135' || $error->cStat == '155'){
            $c = CteOs::
            where('id', $request->id)
            ->first();
            $c->estado = 'CANCELADO';
            $c->save();
        }

        echo json_encode($cte);
    }

    public function cartaCorrecao(Request $request){

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        $cte_service = new CTeOsService([
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb" => (int)$config->ambiente,
            "razaosocial" => $config->razao_social,
            "siglaUF" => $config->UF,
            "cnpj" => $cnpj,
            "schemes" => "PL_CTe_400",
            "versao" => '4.00',
            "proxyConf" => [
                "proxyIp" => "",
                "proxyPort" => "",
                "proxyUser" => "",
                "proxyPass" => ""
            ]
        ], '67');

        $cte = $cte_service->cartaCorrecao($request->id, $request->grupo,
            $request->campo, $request->correcao);
        echo json_encode($cte);
    }

    private function enviarEmailAutomatico($cte){
        $escritorio = EscritorioContabil::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($escritorio != null && $escritorio->envio_automatico_xml_contador){
            $email = $escritorio->email;
            Mail::send('mail.xml_automatico', ['descricao' => 'Envio de CTe Os'], function($m) use ($email, $cte){
                $nomeEmpresa = env('MAIL_NAME');
                $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
                $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
                $emailEnvio = env('MAIL_USERNAME');

                $m->from($emailEnvio, $nomeEmpresa);
                $m->subject('Envio de XML Automático');

                $m->attach(public_path('xml_cte_os/'.$cte->chave.'.xml'));
                $m->to($email);
            });
        }
    }

    public function update(Request $request){
        $data = $request->data;

        $cte_id = $data['cte_id'];
        $tomador_id = $data['tomador_id'];
        $emitente_id = $data['emitente_id'];
        $tomador = $data['tomador'];
        $municipio_envio = $data['municipio_envio'];
        $municipio_inicio = $data['municipio_inicio'];
        $municipio_fim = $data['municipio_fim'];

        $descricao_servico = $data['descricao_servico'];
        $quantidade_carga = $data['quantidade_carga'];
        $valor_receber = $data['valor_receber'];
        $valor_transporte = $data['valor_transporte'];
        $data_viagem = $data['data_viagem'];
        $horario_viagem = $data['horario_viagem'];

        $natureza = $data['natureza'];

        $cst = $data['cst'];
        $percIcms = $data['perc_icms'] ?? 0;

        $veiculo_id = $data['veiculo_id'];
        $obs = $data['obs'] ?? '';

        $cte = CteOs::find($cte_id);

        $cte->tomador_id = $tomador_id;
        $cte->emitente_id = $emitente_id;
        $cte->tomador = $tomador;
        $cte->municipio_envio = $municipio_envio;
        $cte->municipio_inicio = $municipio_inicio;

        $cte->municipio_fim = $municipio_fim;
        $cte->descricao_servico = $descricao_servico;
        $cte->quantidade_carga = str_replace(",", ".", $quantidade_carga);
        $cte->valor_receber = str_replace(",", ".", $valor_receber);
        $cte->valor_transporte = str_replace(",", ".", $valor_transporte);

        $cte->natureza_id = $natureza;
        $cte->veiculo_id = $veiculo_id;
        $cte->observacao = $obs;
        $cte->cst = $cst;
        $cte->perc_icms = $percIcms;
        $cte->data_viagem = $data_viagem;
        $cte->horario_viagem = $horario_viagem;

        try{
            $cte->save();

            return response()->json("ok", 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function detalhar($id){
        $cte = CteOs::findOrFail($id);

        if(valida_objeto($cte)){

            $value = session('user_logged');

            return view("cte_os/detalhe")
            ->with('adm', $value['adm'])
            ->with('cte', $cte)
            ->with('title', "Detalhe de CteOs $id");
        }else{
            return redirect('/403');
        }
    }

    public function estadoFiscal($id){
        $cte = CteOs::findOrFail($id);

        if(valida_objeto($cte)){

            $value = session('user_logged');

            return view("cte_os/alterar_estado_fiscal")
            ->with('adm', $value['adm'])
            ->with('cte', $cte)
            ->with('title', "Detalhe de CteOs $id");
        }else{
            return redirect('/403');
        }
    }

    public function estadoFiscalStore(Request $request){
        try{
            $cte = CteOs::findOrFail($request->cte_id);

            $estado = $request->estado;

            $cte->estado = $estado;
            if ($request->hasFile('file')){
                $public = env('SERVIDOR_WEB') ? 'public/' : '';

                $xml = simplexml_load_file($request->file);

                // echo "<pre>";
                // print_r($xml->CTeOS->infCte->ide->nCT);
                // echo "</pre>";
                // die;
                $chave = substr($xml->CTeOS->infCte->attributes()->Id, 3, 44);
                $file = $request->file;
                $file->move(public_path('xml_cte_os'), $chave.'.xml');
                $cte->chave = $chave;
                $cte->numero_emissao = $xml->CTeOS->infCte->ide->nCT;

            }

            $cte->save();
            session()->flash("mensagem_sucesso", "Estado alterado");

        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Erro: " . $e->getMessage());

        }
        return redirect()->back();
    }

    public function download($id){
        $cte = CteOs::findOrFail($id);
        if(valida_objeto($cte)){
            if(file_exists(public_path('xml_cte_os/').$cte->chave.'.xml')){
                return response()->download(public_path('xml_cte_os/').$cte->chave.'.xml');
            }
        }else{
            return redirect('/403');
        }
    }

    public function imprimir($id){
        $cte = CteOs::findOrFail($id);
        if(valida_objeto($cte)){

            $public = env('SERVIDOR_WEB') ? 'public/' : '';
            if(file_exists($public.'xml_cte_os/'.$cte->chave.'.xml')){
                $xml = safe_file_get_contents($public.'xml_cte_os/'.$cte->chave.'.xml');
        // $docxml = FilesFolders::readFile($xml);

                try {

                    $config = ConfigNota::
                    where('empresa_id', $this->empresa_id)
                    ->first();

                    if($config->logo){
                        $logo = 'data://text/plain;base64,'. base64_encode(safe_file_get_contents($public.'logos/' . $config->logo));
                    }else{
                        $logo = null;
                    }

                    $dacte = new DacteOS($xml);
                    $dacte->debugMode(true);
                    $dacte->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webenf.com.br');
                    // $dacte->monta();

                    $pdf = $dacte->render($logo);
                    header('Content-Type: application/pdf');
                    return response($pdf)
                    ->header('Content-Type', 'application/pdf');
                } catch (InvalidArgumentException $e) {
                    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
                }
            }else{
                echo "Arquivo não encontrado!";
            }
        }else{
            return redirect('/403');
        }
    }

    public function consultar(Request $request){
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        $cte_service = new CTeOsService([
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb" => (int)$config->ambiente,
            "razaosocial" => $config->razao_social,
            "siglaUF" => $config->UF,
            "cnpj" => $cnpj,
            "schemes" => "PL_CTe_400",
            "versao" => '4.00',
            "proxyConf" => [
                "proxyIp" => "",
                "proxyPort" => "",
                "proxyUser" => "",
                "proxyPass" => ""
            ]
        ], '57');

        $c = $cte_service->consultar($request->id);
        echo json_encode($c);
    }

    public function imprimirCCe($id){
        $cte = CteOs::
        where('id', $id)
        ->first();
        if(valida_objeto($cte)){
            $public = env('SERVIDOR_WEB') ? 'public/' : '';
            if(file_exists($public.'xml_cte_os_correcao/'.$cte->chave.'.xml')){

                $xml = safe_file_get_contents($public.'xml_cte_os_correcao/'.$cte->chave.'.xml');

                $config = ConfigNota::
                where('empresa_id', $this->empresa_id)
                ->first();

                if($config->logo){
                    $logo = 'data://text/plain;base64,'. base64_encode(safe_file_get_contents($public.'logos/' . $config->logo));
                }else{
                    $logo = null;
                }

                $dadosEmitente = $this->getEmitente();

                try {

                    $daevento = new Daevento($xml, $dadosEmitente);
                    $daevento->debugMode(true);
                    $pdf = $daevento->render($logo);
                    header('Content-Type: application/pdf');
                    return response($pdf)
                    ->header('Content-Type', 'application/pdf');

                } catch (InvalidArgumentException $e) {
                    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
                }
            }else{
                echo "Arquivo não encontrado!";
            }
        }else{
            return redirect('/403');
        }
    }

    public function imprimirCancela($id){
        $cte = CteOs::
        where('id', $id)
        ->first();
        if(valida_objeto($cte)){
            $public = env('SERVIDOR_WEB') ? 'public/' : '';
            if(file_exists($public.'xml_cte_os_cancelada/'.$cte->chave.'.xml')){
                $xml = safe_file_get_contents($public.'xml_cte_os_cancelada/'.$cte->chave.'.xml');
                $config = ConfigNota::
                where('empresa_id', $this->empresa_id)
                ->first();

                if($config->logo){
                    $logo = 'data://text/plain;base64,'. base64_encode(safe_file_get_contents($public.'logos/' . $config->logo));
                }else{
                    $logo = null;
                }

                $dadosEmitente = $this->getEmitente();

                try {

                    $daevento = new Daevento($xml, $dadosEmitente);
                    $daevento->debugMode(true);
                    $pdf = $daevento->render($logo);
                    header('Content-Type: application/pdf');
                    return response($pdf)
                    ->header('Content-Type', 'application/pdf');

                } catch (InvalidArgumentException $e) {
                    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
                }
            }else{
                echo "Arquivo não encontrado!";
            }
        }else{
            return redirect('/403');
        }
    }

    private function getEmitente(){
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();
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

    public function enviarXml(Request $request){
        $email = $request->email;
        $id = $request->id;
        $cte = CteOs::
        where('id', $id)
        ->first();
        if(valida_objeto($cte)){
            $this->criarPdfParaEnvio($cte);
            $value = session('user_logged');
            Mail::send('mail.xml_send_cte_os', ['cte' => $cte, 'usuario' => $value['nome']], function($m) use ($cte, $email){
                $public = env('SERVIDOR_WEB') ? 'public/' : '';
                $nomeEmpresa = env('SMS_NOME_EMPRESA');
                $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
                $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
                $emailEnvio = env('MAIL_USERNAME');

                $m->from($emailEnvio, $nomeEmpresa);
                $m->subject('Envio de XML CTe OS ' . $cte->nuero_emissao);
                $m->attach($public.'xml_cte_os/'.$cte->chave . '.xml');
                $m->attach($public.'pdf/CTeOs.pdf');
                $m->to($email);
            });
            return "ok";
        }else{
            return redirect('/403');
        }
    }

    private function criarPdfParaEnvio($cte){
        $public = env('SERVIDOR_WEB') ? 'public/' : '';
        $xml = safe_file_get_contents($public.'xml_cte_os/'.$cte->chave.'.xml');
        $logo = 'data://text/plain;base64,'. base64_encode(safe_file_get_contents($public.'imgs/logo.jpg'));
        // $docxml = FilesFolders::readFile($xml);

        try {

            $dacte = new DacteOs($xml);
            // $dacte->debugMode(true);
            $dacte->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webenf.com.br');
            // $dacte->monta();
            $pdf = $dacte->render();
            header('Content-Type: application/pdf');
            safe_file_put_contents($public.'pdf/CTeOs.pdf',$pdf);
        } catch (InvalidArgumentException $e) {
            echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
        }
    }

}
