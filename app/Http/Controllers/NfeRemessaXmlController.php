<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use NFePHP\DA\NFe\Danfe;
use App\Services\CustomDanfe as Danfe;
use App\Models\RemessaNfe;
use NFePHP\DA\NFe\DanfeSimples;
use NFePHP\DA\NFe\Daevento;
use App\Services\NFeRemessaService;
use App\Models\Filial;
use App\Models\ConfigNota;
use App\Models\EscritorioContabil;
use Mail;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class NfeRemessaXmlController extends Controller
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

    public function gerarXml($id){

        $item = RemessaNfe::
        where('empresa_id', $this->empresa_id)
        ->where('id', $id)
        ->first();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $isFilial = $item->filial_id;
        if($item->filial_id == null){
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();
        }else{
            $config = Filial::findOrFail($item->filial_id);
        }

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        $nfe_service = new NFeRemessaService([
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb" => (int)$config->ambiente,
            "razaosocial" => $config->razao_social,
            "siglaUF" => $config->UF,
            "cnpj" => $cnpj,
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
            "tokenIBPT" => "",
            "CSC" => $config->csc,
            "CSCid" => $config->csc_id,
            "is_filial" => $isFilial
        ]);
        $nfe = $nfe_service->gerarNFe($item);

        if(!isset($nfe['erros_xml'])){
            $xml = $nfe['xml'];
            return response($xml)
            ->header('Content-Type', 'application/xml');
        }else{
            print_r($nfe['erros_xml']);
        }
    }

    public function rederizarDanfe($id){

        $item = RemessaNfe::
        where('empresa_id', $this->empresa_id)
        ->where('id', $id)
        ->first();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $isFilial = $item->filial_id;
        if($item->filial_id == null){
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();
        }else{
            $config = Filial::findOrFail($item->filial_id);
        }

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        $nfe_service = new NFeRemessaService([
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb" => (int)$config->ambiente,
            "razaosocial" => $config->razao_social,
            "siglaUF" => $config->UF,
            "cnpj" => $cnpj,
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
            "tokenIBPT" => "",
            "CSC" => $config->csc,
            "CSCid" => $config->csc_id,
            "is_filial" => $isFilial
        ]);
        $nfe = $nfe_service->gerarNFe($item);

        if(!isset($nfe['erros_xml'])){
            $xml = $nfe['xml'];

            if($config->logo){
                $logo = 'data://text/plain;base64,'. base64_encode(safe_file_get_contents(public_path('logos/') . $config->logo));
            }else{
                $logo = null;
            }

            try {
                $danfe = new Danfe($xml);
                    // $id = $danfe->monta();
                $danfe->setVUnComCasasDec($config->casas_decimais);

                $pdf = $danfe->render();
                header("Content-Disposition: ; filename=DANFE Temporária.pdf");
                return response($pdf)
                ->header('Content-Type', 'application/pdf');
            } catch (InvalidArgumentException $e) {
                echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
            }
        }else{
            print_r($nfe['erros_xml']);
        }
    }

    public function transmitir(Request $request){

        $item = RemessaNfe::
        where('empresa_id', $this->empresa_id)
        ->where('id', $request->nfeid)
        ->first();

        $isFilial = $item->filial_id;
        if($item->filial_id == null){
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();
        }else{
            $config = Filial::findOrFail($item->filial_id);
            if($config->arquivo_certificado == null){
                echo "Necessário o certificado para realizar esta ação!";
                die;
            }
        }

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        $nfe_service = new NFeRemessaService([
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb" => (int)$config->ambiente,
            "razaosocial" => $config->razao_social,
            "siglaUF" => $config->UF,
            "cnpj" => $cnpj,
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
            "tokenIBPT" => "",
            "CSC" => $config->csc,
            "CSCid" => $config->csc_id,
            "is_filial" => $isFilial
        ]);

        if($item->estado == 'rejeitado' || $item->estado == 'novo'){

            $nfe = $nfe_service->gerarNFe($item);
            if(!isset($nfe['erros_xml'])){
            // file_put_contents('xml/teste2.xml', $nfe['xml']);
            // return response()->json($nfe, 200);
                $signed = $nfe_service->sign($nfe['xml']);
                $resultado = $nfe_service->transmitir($signed, $nfe['chave'], $item->id);

                if(substr($resultado, 0, 4) != 'Erro'){
                    $item->chave = $nfe['chave'];
                    $item->estado = 'aprovado';
                    $item->nSerie = $config->numero_serie_nfe;
                    $item->data_emissao = date('Y-m-d H:i:s');

                    $item->numero_nfe = $nfe['nNf'];
                    $item->save();

                    $config->ultimo_numero_nfe = $nfe['nNf'];
                    $config->save();

                    $this->enviarEmailAutomatico($item);

                    $file = safe_file_get_contents(public_path('xml_nfe/'.$nfe['chave'].'.xml'));
                    importaXmlSieg($file, $this->empresa_id);

                }else{
                    if($item->signed_xml == null){
                        $item->signed_xml = $signed;
                    }
                    $item->estado = 'rejeitado';
                    $item->chave = $nfe['chave'];
                    $item->save();
                }
                echo json_encode($resultado);
            }else{
                return response()->json($nfe['erros_xml'], 401);
            }

        }else{
            echo json_encode("Apro");
        }

    }

    public function gerarNfWithXml(Request $request){

        $vendaId = $request->vendaId;
        $venda = RemessaNfe::findOrFail($request->id);

        $isFilial = $venda->filial_id;
        if($venda->filial_id == null){
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();
        }else{
            $config = Filial::findOrFail($venda->filial_id);
            if($config->arquivo_certificado == null){
                echo "Necessário o certificado para realizar esta ação!";
                die;
            }
        }

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

         $nfe_service = new NFeRemessaService([
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb" => (int)$config->ambiente,
            "razaosocial" => $config->razao_social,
            "siglaUF" => $config->UF,
            "cnpj" => $cnpj,
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
            "tokenIBPT" => "",
            "CSC" => $config->csc,
            "CSCid" => $config->csc_id,
            "is_filial" => $isFilial
        ]);

        if($venda->estado == 'rejeitado' || $venda->estado == 'novo'){
            header('Content-type: text/html; charset=UTF-8');
            $xml = $request->xml;
            $exp = simplexml_load_string($xml);
            $array = json_decode(json_encode((array) $exp), true);

            $chave = (string)substr($array['infNFe']['@attributes']['Id'], 3, 47);
            $nNF = (string)$array['infNFe']['ide']['nNF'];

            $signed = $nfe_service->sign($xml);
            $resultado = $nfe_service->transmitir($signed, $chave);

            if(substr($resultado, 0, 4) != 'Erro'){
                $venda->chave = $chave;
                $venda->estado = 'aprovado';
                $venda->nSerie = $config->numero_serie_nfe;
                $venda->data_emissao = date('Y-m-d H:i:s');

                $venda->numero_nfe = $nNF;
                $venda->save();

                $config->ultimo_numero_nfe = $nNF;
                $config->save();


                $this->enviarEmailAutomatico($venda);

                $file = safe_file_get_contents(public_path('xml_nfe/'.$chave.'.xml'));
                importaXmlSieg($file, $this->empresa_id);

            }else{
                $venda->estado = 'rejeitado';
                $venda->chave = $chave;
                $venda->save();
            }
            echo json_encode($resultado);

        }else{
            echo json_encode("Apro");
        }

    }

    private function enviarEmailAutomatico($item){
        $escritorio = EscritorioContabil::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($escritorio != null && $escritorio->envio_automatico_xml_contador){
            $email = $escritorio->email;
            Mail::send('mail.xml_automatico', ['descricao' => 'Envio de NFe'], function($m) use ($email, $item){
                $nomeEmpresa = env('MAIL_NAME');
                $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
                $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
                $emailEnvio = env('MAIL_USERNAME');

                $m->from($emailEnvio, $nomeEmpresa);
                $m->subject('Envio de XML Automático');

                $m->attach(public_path('xml_nfe/'.$item->chave.'.xml'));
                $m->to($email);
            });
        }
    }

    public function imprimir($id){

        $item = RemessaNfe::
        where('id', $id)
        ->where('empresa_id', $this->empresa_id)
        ->first();
        if(valida_objeto($item)){

            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();

            if(file_exists(public_path('xml_nfe/').$item->chave.'.xml')){
                $xml = safe_file_get_contents(public_path('xml_nfe/').$item->chave.'.xml');
                if($config->logo){
                    $logo = 'data://text/plain;base64,'. base64_encode(safe_file_get_contents(public_path('logos/') . $config->logo));
                }else{
                    $logo = null;
                }

                try {
                    $danfe = new Danfe($xml);
                    $danfe->setVUnComCasasDec($config->casas_decimais);

                    // $id = $danfe->monta($logo);
                    $pdf = $danfe->render($logo);
                    header("Content-Disposition: ; filename=DANFE $item->numero_nfe.pdf");
                    return response($pdf)
                    ->header('Content-Type', 'application/pdf');
                } catch (InvalidArgumentException $e) {
                    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
                }
            }else{
                echo "Arquivo XML não encontrado!!";
            }
        }else{
            return redirect('/403');
        }
    }

    public function consultar(Request $request){

        $item = RemessaNfe::
        where('id', $request->id)
        ->where('empresa_id', $this->empresa_id)
        ->first();
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        $nfe_service = new NFeRemessaService([
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb" => (int)$config->ambiente,
            "razaosocial" => $config->razao_social,
            "siglaUF" => $config->UF,
            "cnpj" => $cnpj,
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
            "tokenIBPT" => "",
            "CSC" => $config->csc,
            "CSCid" => $config->csc_id
        ]);
        $c = $nfe_service->consultar($item);
        echo json_encode($c);
    }

    public function cartaCorrecao(Request $request){

        $item = RemessaNfe::
        where('id', $request->id)
        ->where('empresa_id', $this->empresa_id)
        ->first();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $isFilial = $item->filial_id;
        if($item->filial_id != null){
            $config = Filial::findOrFail($item->filial_id);
        }

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        $nfe_service = new NFeRemessaService([
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
        ]);

        $nfe = $nfe_service->cartaCorrecao($item, $request->correcao);
        echo json_encode($nfe);
    }

    public function imprimirCce($id){
        $item = RemessaNfe::
        where('id', $id)
        ->where('empresa_id', $this->empresa_id)
        ->first();

        if($item->sequencia_cce > 0){

            if(file_exists(public_path('xml_nfe_correcao/').$item->chave.'.xml')){
                $xml = safe_file_get_contents(public_path('xml_nfe_correcao/').$item->chave.'.xml');

                $config = ConfigNota::
                where('empresa_id', $this->empresa_id)
                ->first();

                if($config->logo){
                    $logo = 'data://text/plain;base64,'. base64_encode(safe_file_get_contents(public_path('logos/') . $config->logo));
                }else{
                    $logo = null;
                }

                $dadosEmitente = $this->getEmitente($item->filial);

                try {
                    $daevento = new Daevento($xml, $dadosEmitente);
                    $daevento->debugMode(true);
                    $pdf = $daevento->render($logo);

                    return response($pdf)
                    ->header('Content-Type', 'application/pdf');
                } catch (InvalidArgumentException $e) {
                    echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
                }
            }else{
                echo "Arquivo XML não encontrado!!";
            }
        }else{
            echo "<center><h1>Este documento não possui evento de correção!<h1></center>";
        }
    }

    public function imprimirCancela($id){
        $item = RemessaNfe::
        where('id', $id)
        ->where('empresa_id', $this->empresa_id)
        ->first();

        if($item->estado == 'cancelado'){
            try {
                if(file_exists(public_path('xml_nfe_cancelada/').$item->chave.'.xml')){
                    $xml = safe_file_get_contents(public_path('xml_nfe_cancelada/').$item->chave.'.xml');

                    $config = ConfigNota::
                    where('empresa_id', $this->empresa_id)
                    ->first();

                    if($config->logo){
                        $logo = 'data://text/plain;base64,'. base64_encode(safe_file_get_contents(public_path('logos/') . $config->logo));
                    }else{
                        $logo = null;
                    }

                    $dadosEmitente = $this->getEmitente($item->filial);

                    $daevento = new Daevento($xml, $dadosEmitente);
                    $daevento->debugMode(true);
                    $pdf = $daevento->render($logo);
                // header('Content-Type: application/pdf');
                // echo $pdf;
                    return response($pdf)
                    ->header('Content-Type', 'application/pdf');
                }else{
                    echo "Arquivo XML não encontrado!!";
                }
            } catch (InvalidArgumentException $e) {
                echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
            }
        }else{
            echo "<center><h1>Este documento não possui evento de cancelamento!<h1></center>";
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

    public function cancelar(Request $request){
        $item = RemessaNfe::
        where('id', $request->id)
        ->where('empresa_id', $this->empresa_id)
        ->first();
        $isFilial = $item->filial_id;
        if($item->filial_id == null){
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();
        }else{
            $config = Filial::findOrFail($item->filial_id);
        }

        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

        $nfe_service = new NFeRemessaService([
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb" => (int)$config->ambiente,
            "razaosocial" => $config->razao_social,
            "siglaUF" => $config->UF,
            "cnpj" => $cnpj,
            "schemes" => "PL_009_V4",
            "versao" => "4.00",
            "tokenIBPT" => "",
            "CSC" => $config->csc,
            "CSCid" => $config->csc_id
        ]);

        $nfe = $nfe_service->cancelar($item, $request->justificativa);

        if(!isset($nfe['erro'])){

            $item->estado = 'cancelado';
            $item->save();

            $file = safe_file_get_contents(public_path('xml_nfe_cancelada/'.$item->chave.'.xml'));
            importaXmlSieg($file, $this->empresa_id);

            return response()->json($nfe, 200);

        }else{
            return response()->json($nfe['data'], $nfe['status']);
        }

    }

    public function baixarXml($id){
        $item = RemessaNfe::findOrFail($id);
        if(valida_objeto($item)){

            if(file_exists(public_path('xml_nfe/').$item->chave.'.xml')){

                return response()->download(public_path('xml_nfe/').$item->chave.'.xml');
            }else{
                echo "Arquivo XML não encontrado!!";
            }
        }else{
            return redirect('/403');
        }

    }

    public function enviarXml(Request $request){
        $email = $request->email;
        $id = $request->id;

        if(!is_dir(public_path('vendas_temp'))){
            mkdir(public_path('vendas_temp'), 0777, true);
        }

        $item = RemessaNfe::
        where('id', $id)
        ->where('empresa_id', $this->empresa_id)
        ->first();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($item->chave != ""){
            $this->criarPdfParaEnvio($item);
        }
        $value = session('user_logged');

        if($config->usar_email_proprio){
            $send = $this->enviaEmailPHPMailer($item, $email, $config);
            if(isset($send['erro'])){
                return response()->json($send['erro'], 401);
            }
            return "ok";
        }else{
            Mail::send('mail.xml_send', ['emissao' => $item->data_emissao, 'nf' => $item->numero_nfe,
                'valor' => $item->valor_total, 'usuario' => $value['nome'], 'venda' => $item, 'config' => $config], function($m) use ($item, $email){


                    $nomeEmpresa = env('MAIL_NAME');
                    $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
                    $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
                    $emailEnvio = env('MAIL_USERNAME');

                    $m->from($emailEnvio, $nomeEmpresa);
                    $subject = "Envio de NFe #$item->id";
                    if($item->numero_nfe > 0){
                        $subject .= " | NFe: $item->numero_nfe";
                    }
                    $m->subject($subject);

                    if($item->chave != ""){
                        $m->attach(public_path('xml_nfe/').$item->chave.'.xml');
                        $m->attach(public_path('pdf/DANFE.pdf'));
                    }

                    $m->to($email);
                });
            return "ok";
        }
    }

    private function enviaEmailPHPMailer($item, $email, $config){
        $emailConfig = EmailConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($emailConfig == null){
            return [
                'erro' => 'Primeiramente configure seu email'
            ];
        }

        $value = session('user_logged');

        $mail = new PHPMailer(true);

        try {
            if($emailConfig->smtp_debug){
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            }
            $mail->isSMTP();
            $mail->Host = $emailConfig->host;
            $mail->SMTPAuth = $emailConfig->smtp_auth;
            $mail->Username = $emailConfig->email;
            $mail->Password = $emailConfig->senha;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $emailConfig->porta;

            $mail->setFrom($emailConfig->email, $emailConfig->nome);
            $mail->addAddress($email);

            $mail->addAttachment(public_path('pdf/DANFE.pdf'));

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';

            $mail->Subject = "Envio de NFe #$item->id";
            $body = view('mail.xml_send', ['emissao' => $item->data_emissao, 'nf' => $item->numero_nfe,
                'valor' => $item->valor_total, 'usuario' => $value['nome'], 'venda' => $item, 'config' => $config]);
            $mail->Body = $body;
            $mail->send();
            return [
                'sucesso' => true
            ];
        } catch (Exception $e) {
            return [
                'erro' => $mail->ErrorInfo
            ];
            // echo "Message could; not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

    private function criarPdfParaEnvio($venda){

        $xml = safe_file_get_contents(public_path('xml_nfe/').$venda->chave.'.xml');

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
            file_put_contents(public_path('pdf/DANFE.pdf'),$pdf);
        } catch (InvalidArgumentException $e) {
            echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
        }
    }

    public function estadoFiscal($id){
        $item = RemessaNfe::findOrFail($id);
        $value = session('user_logged');
        if($value['adm'] == 0) return redirect()->back();

        if(valida_objeto($item)){

            return view("remessa_nfe/alterar_estado_fiscal")
            ->with('item', $item)
            ->with('title', "Alterar estado NFe $id");
        }else{
            return redirect('/403');
        }
    }

    public function estadoFiscalPut(Request $request, $id){
        try{
            $item = RemessaNfe::find($id);
            $estado = $request->estado;

            $item->estado = $estado;
            if ($request->hasFile('file')){

                $xml = simplexml_load_file($request->file);
                $chave = substr($xml->NFe->infNFe->attributes()->Id, 3, 44);
                $file = $request->file;
                $dhEmi = \Carbon\Carbon::parse($xml->NFe->infNFe->ide->dhEmi)->format('Y-m-d H:i');

                $file->move(public_path('xml_nfe'), $chave.'.xml');
                $item->chave = $chave;
                $item->data_emissao = $dhEmi;
                $item->numero_nfe = (int)$xml->NFe->infNFe->ide->nNF;

                if($item->filial_id != null){
                    $config = Filial::findOrFail($item->filial_id);
                    $config->ultimo_numero_nfe = (int)$xml->NFe->infNFe->ide->nNF;
                    $config->save();
                }

            }

            $item->save();
            session()->flash("mensagem_sucesso", "Estado alterado");

        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Erro: " . $e->getMessage());

        }
        return redirect()->back();
    }

}
