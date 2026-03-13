<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfigNota;
use App\Models\Nfse as NotaServico;
use CloudDfe\SdkPHP\Nfse;

class NfseIntegraNotasController extends Controller
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

    public function enviar(Request $request){

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $item = NotaServico::findOrFail($request->id);
        if($item->estado == 'aprovado'){
            return response()->json("Este documento esta aprovado", 401);
        }

        if($item->estado == 'cancelado'){
            return response()->json("Este documento esta cancelado", 401);
        }

        if(!is_dir(public_path('nfse_doc'))){
            mkdir(public_path('nfse_doc'), 0777, true);
        }

        $params = [
            'token' => $config->token_nfse,
            'ambiente' => Nfse::AMBIENTE_PRODUCAO,
            // 'ambiente' => $config->ambiente == 2 ? Nfse::AMBIENTE_HOMOLOGACAO : Nfse::AMBIENTE_PRODUCAO,
            'options' => [
                'debug' => false,
                'timeout' => 60,
                'port' => 443,
                'http_version' => CURL_HTTP_VERSION_NONE
            ]
        ];
        $nfse = new Nfse($params);
        $servico = $item->servico;
        try {

            $doc = preg_replace('/[^0-9]/', '', $item->documento);
            $im = preg_replace('/[^0-9]/', '', $item->im);
            $ie = preg_replace('/[^0-9]/', '', $item->ie);

            $payload = [
                "numero" => $config->ultimo_numero_nfse+1,
                "serie" => $config->numero_serie_nfse,
                "tipo" => "1",
                "status" => "1",
                "data_emissao" => date("Y-m-d\TH:i:sP"),
                "data_competencia" => date("Y-m-d\TH:i:sP"),
                "regime_tributacao" => "6",
                "tomador" => [
                    "cnpj" => strlen($doc) == 14 ? $doc : null,
                    "cpf" => strlen($doc) == 11 ? $doc : null,
                    "im" => $im ? $im : null,
                    "ie" => $ie ? $ie : null,
                    "razao_social" => $item->razao_social,
                    "endereco" => [
                        "logradouro" => $this->retiraAcentos($item->rua),
                        "numero" => $this->retiraAcentos($item->numero),
                        "complemento" => $this->retiraAcentos($item->complemento),
                        "bairro" => $this->retiraAcentos($item->bairro),
                        "codigo_municipio" => $item->cidade->codigo,
                        "uf" => $item->cidade->uf,
                        "cep" => preg_replace('/[^0-9]/', '', $item->cep)
                    ]
                ],
                "servico" => [
                    "codigo" => $servico->codigo_servico,
                    "codigo_tributacao_municipio" => $servico->codigo_tributacao_municipio,
                    "discriminacao" => $this->retiraAcentos($servico->discriminacao),
                    "codigo_municipio" => $config->codMun,
                    "valor_servicos" => $servico->valor_servico,

                    "valor_aliquota" => $servico->aliquota_iss,
                    "itens" => [
                        [
                            "codigo" => $servico->codigo_servico,
                            "codigo_tributacao_municipio" => $servico->codigo_tributacao_municipio,
                            "discriminacao" => $this->retiraAcentos($servico->discriminacao),
                            "codigo_municipio" => $config->codMun,
                            "valor_servicos" => $servico->valor_servico,
                            "valor_aliquota" => $servico->aliquota_iss,
                        ]
                    ]
                ],

            ];

            // return response()->json($payload, 404);

            $resp = $nfse->cria($payload);
            if($resp->sucesso == true){
                if(isset($resp->chave)){
                    $item->chave = $resp->chave;
                    $item->save();
                }

                sleep(15);
                $tentativa = 1;
                while ($tentativa <= 5) {
                    $payload = [
                        'chave' => $resp->chave
                    ];
                    $resp = $nfse->consulta($payload);
                    if ($resp->codigo != 5023) {
                        if ($resp->sucesso) {
                    // autorizado

                            $item->estado = 'aprovado';
                            $item->url_pdf_nfse = $resp->link_pdf;
                            $item->numero_nfse = $resp->numero;
                            $item->codigo_verificacao = $resp->codigo_verificacao;

                            $item->save();

                            $config->ultimo_numero_nfse = (int)$resp->numero;
                            $config->save();
                            $xml = $resp->xml;
                            safe_file_put_contents(public_path('nfse_doc/')."$resp->chave.xml", $xml);

                            if($resp->pdf){
                                $pdf = base64_decode($resp->pdf);
                                safe_file_put_contents(public_path('nfse_pdf/')."$resp->chave.pdf", $pdf);
                            }
                            return response()->json($resp, 200);
                        } else {
                            return response()->json($resp, 200);
                        }
                    }
                    sleep(3);
                    $tentativa++;
                }
                return response()->json($resp, 200);
            }else{
                if($resp->codigo == 5008){
                    $item->chave = $resp->chave;
                    $item->save();
                }
            }
            return response()->json($resp, 404);

        }catch (\Exception $e) {
            return response()->json($e->getMessage(), 403);
        }

    }

    private function retiraAcentos($texto){
        return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/", "/(ç)/", "/(&)/"),explode(" ","a A e E i I o O u U n N c e"),$texto);
    }

    public function consultar(Request $request){
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $params = [
            'token' => $config->token_nfse,
            'ambiente' => Nfse::AMBIENTE_PRODUCAO,
            // 'ambiente' => $config->ambiente == 2 ? Nfse::AMBIENTE_HOMOLOGACAO : Nfse::AMBIENTE_PRODUCAO,
            'options' => [
                'debug' => false,
                'timeout' => 60,
                'port' => 443,
                'http_version' => CURL_HTTP_VERSION_NONE
            ]
        ];
        try{
            $nfse = new Nfse($params);
            $item = NotaServico::findOrFail($request->id);

            $payload = [
                'chave' => $item->chave
            ];
            $resp = $nfse->consulta($payload);
            if($resp->sucesso == true){
                if($resp->codigo == 100){

                    $item->estado = 'aprovado';
                    $item->url_pdf_nfse = $resp->link_pdf;
                    $item->numero_nfse = $resp->numero;
                    $item->codigo_verificacao = $resp->codigo_verificacao;

                    $item->save();

                    $config->ultimo_numero_nfse = (int)$resp->numero;
                    $config->save();

                    if($resp->pdf){
                        $pdf = base64_decode($resp->pdf);
                        safe_file_put_contents(public_path('nfse_pdf/')."$item->chave.pdf", $pdf);
                    }

                    $xml = $resp->xml;
                    safe_file_put_contents(public_path('nfse_doc/')."$item->chave.xml", $xml);
                }
                return response()->json($resp, 200);
            }
            return response()->json($resp, 404);

        }catch (\Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function cancelar(Request $request){
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $params = [
            'token' => $config->token_nfse,
            'ambiente' => Nfse::AMBIENTE_PRODUCAO,
            // 'ambiente' => $config->ambiente == 2 ? Nfse::AMBIENTE_HOMOLOGACAO : Nfse::AMBIENTE_PRODUCAO,
            'options' => [
                'debug' => false,
                'timeout' => 60,
                'port' => 443,
                'http_version' => CURL_HTTP_VERSION_NONE
            ]
        ];
        $nfse = new Nfse($params);
        $item = NotaServico::findOrFail($request->id);

        $payload = [
            'chave' => $item->chave,
            'justificativa' => $request->motivo
        ];
        $resp = $nfse->cancela($payload);
        if($resp->sucesso == true){
            return response()->json($resp, 404);
        }

        return response()->json($resp, 404);

    }

    public function previewXml($id){
        if(!is_dir(public_path('nfse_temp'))){
            mkdir(public_path('nfse_temp'), 0777, true);
        }
        $item = NotaServico::findOrFail($id);

        $config = ConfigNota::
        where('empresa_id', $item->empresa_id)
        ->first();

        $params = [
            'token' => $config->token_nfse,
            'ambiente' => Nfse::AMBIENTE_PRODUCAO,
            // 'ambiente' => $config->ambiente == 2 ? Nfse::AMBIENTE_HOMOLOGACAO : Nfse::AMBIENTE_PRODUCAO,
            'options' => [
                'debug' => false,
                'timeout' => 60,
                'port' => 443,
                'http_version' => CURL_HTTP_VERSION_NONE
            ]
        ];
        $nfse = new Nfse($params);
        $servico = $item->servico;
        try {

            $doc = preg_replace('/[^0-9]/', '', $item->documento);
            $im = preg_replace('/[^0-9]/', '', $item->im);
            $ie = preg_replace('/[^0-9]/', '', $item->ie);

            $payload = [
                "numero" => $config->ultimo_numero_nfse+1,
                "serie" => $config->numero_serie_nfse,
                "tipo" => "1",
                "status" => "1",
                "data_emissao" => date("Y-m-d\TH:i:sP"),
                "data_competencia" => date("Y-m-d\TH:i:sP"),
                "regime_tributacao" => "6",
                "tomador" => [
                    "cnpj" => strlen($doc) == 14 ? $doc : null,
                    "cpf" => strlen($doc) == 11 ? $doc : null,
                    "im" => $im ? $im : null,
                    "ie" => $ie ? $ie : null,
                    "razao_social" => $item->razao_social,
                    "endereco" => [
                        "logradouro" => $this->retiraAcentos($item->rua),
                        "numero" => $this->retiraAcentos($item->numero),
                        "complemento" => $this->retiraAcentos($item->complemento),
                        "bairro" => $this->retiraAcentos($item->bairro),
                        "codigo_municipio" => $item->cidade->codigo,
                        "uf" => $item->cidade->uf,
                        "cep" => preg_replace('/[^0-9]/', '', $item->cep)
                    ]
                ],
                "servico" => [
                    "codigo_tributacao_municipio" => $servico->codigo_tributacao_municipio,
                    "discriminacao" => $this->retiraAcentos($servico->discriminacao),
                    "codigo_municipio" => $config->codMun,
                    "valor_servicos" => $servico->valor_servico,
                    // "valor_pis" => "1.00",
                    // "valor_cofins" => "1.00",
                    // "valor_inss" => "1.00",
                    // "valor_ir" => "1.00",
                    // "valor_csll" => "1.00",
                    // "valor_outras" => "1.00",
                    "valor_aliquota" => $servico->aliquota_iss,
                    "codigo" => $servico->codigo_servico,
                    // "valor_desconto_incondicionado" => "1.00",
                    "itens" => [
                        [
                            "codigo" => $servico->codigo_servico,
                            "codigo_tributacao_municipio" => $servico->codigo_tributacao_municipio,
                            "discriminacao" => $this->retiraAcentos($servico->discriminacao),
                            "codigo_municipio" => $config->codMun,
                            "valor_servicos" => $servico->valor_servico,
                            // "valor_pis" => "1.00",
                            // "valor_cofins" => "1.00",
                            // "valor_inss" => "1.00",
                            // "valor_ir" => "1.00",
                            // "valor_csll" => "1.00",
                            // "valor_outras" => "1.00",
                            "valor_aliquota" => $servico->aliquota_iss,
                            // "valor_desconto_incondicionado" => "1.00"
                        ]
                    ]
                ],

            ];

            // return response()->json($payload, 404);
            $rute = "nfse_temp/temp.pdf";
            $resp = $nfse->preview($payload);

            if(isset($resp->pdf)){
                $pdf_b64 = base64_decode($resp->pdf);

                if(safe_file_put_contents($rute, $pdf_b64)){
                    header("Content-type: application/pdf");
                    echo $pdf_b64;
                }
            }else{
                dd($resp);
            }

        }catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
            return redirect()->back();
        }

    }

}
