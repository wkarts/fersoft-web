<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NfseConfig;
use App\Models\Cidade;
use App\Models\ConfigSystem;
use App\Models\ConfigNota;
use CloudDfe\SdkPHP\Softhouse;
use CloudDfe\SdkPHP\Emitente;
use Illuminate\Support\Str;
use CloudDfe\SdkPHP\Certificado;

class NfseConfigController extends Controller
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

    public function certificado(Request $request){
        $config = ConfigSystem::first();

        if($config == null){
            session()->flash('mensagem_erro', 'Sem dados de configuração superadmin!');
            return redirect()->back();
        }

        $certificadoApi = $this->getCertificado();
        return view('nfse_config.certificado', compact('certificadoApi'));

    }

    private function getCertificado(){
        $item = NfseConfig::where('empresa_id', $this->empresa_id)
        ->first();
        $params = [
            'token' => $item->token,
            'ambiente' => Certificado::AMBIENTE_PRODUCAO,
            'options' => [
                'debug' => false,
                'timeout' => 60,
                'port' => 443,
                'http_version' => CURL_HTTP_VERSION_NONE
            ]
        ];
        $certificado = new Certificado($params);
        $resp = $certificado->mostra();

        return $resp;
    }

    public function index(Request $request){
        $config = ConfigSystem::first();
        if($config == null){
            session()->flash('mensagem_erro', 'Sem dados de configuração superadmin!');
            return redirect()->back();
        }

        if($config->token_integra_notas == null){
            session()->flash('mensagem_erro', 'Sem dados do token integra notas de configuração superadmin!');
            return redirect()->back();
        }
        $item = NfseConfig::where('empresa_id', $this->empresa_id)
        ->first();

        $cidades = Cidade::all();

        $configNota = ConfigNota::where('empresa_id', $this->empresa_id)
        ->first();

        if($configNota == null){
            session()->flash('mensagem_erro', 'Configure o emitente!');
            return redirect('/configNF');
        }

        $tokenNfse = $configNota->token_nfse;
        return view('nfse_config.index', compact('item', 'cidades', 'tokenNfse'));
    }

    public function store(Request $request){
        try{
            $item = NfseConfig::create($request->all());
            $resp = $this->storeSofthouse($request);
            if($resp->codigo == 200){
                $item->token = $resp->token;
                $item->save();

                $configNota = ConfigNota::where('empresa_id', $this->empresa_id)
                ->first();

                $configNota->token_nfse = $resp->token;
                $configNota->integracao_nfse = 'integranotas';
                $configNota->save();

                session()->flash("mensagem_sucesso", "Configurado com sucesso!");
            }else{
                session()->flash('mensagem_erro', $resp->mensagem);
            }

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();
    }

    private function storeSofthouse($request){
        try {
            $config = ConfigSystem::first();

            $params = [
                'token' => $config->token_integra_notas,
                'ambiente' => Softhouse::AMBIENTE_PRODUCAO,
                'options' => [
                    'debug' => false,
                    'timeout' => 60,
                    'port' => 443,
                    'http_version' => CURL_HTTP_VERSION_NONE
                ]
            ];
            $softhouse = new Softhouse($params);
            $documento = preg_replace('/[^0-9]/', '', $request->documento);
            $telefone = preg_replace('/[^0-9]/', '', $request->telefone);
            $cep = preg_replace('/[^0-9]/', '', $request->cep);

            $cidade = Cidade::findOrFail($request->cidade_id);

            $payload = [
                "nome" => $request->nome,
                "razao" => $request->razao_social,
                "cnae" => $request->cnae,
                "crt" => $request->regime == 'simples' ? 1 : 3,
                "ie" => $request->ie,
                "im" => $request->im,
                "login_prefeitura" => $request->login_prefeitura,
                "senha_prefeitura" => $request->senha_prefeitura,
                "telefone" => $telefone,
                "email" => $request->email,
                "rua" => $request->rua,
                "numero" => $request->numero,
                "complemento" => $request->complemento,
                "bairro" => $request->bairro,
                "municipio" => $cidade->nome, 
                "cmun" => $cidade->codigo, 
                "uf" => $cidade->uf, 
                "cep" => $cep,
                "plano" => 'Emitente',
                "documentos" => [
                    "nfse" => true,
                ]
            ];

            if(strlen($documento) == 11){
                $payload['cpf'] = $documento;
            }else{
                $payload['cnpj'] = $documento;
            }
            // dd($payload);
            $resp = $softhouse->criaEmitente($payload);

            return $resp;
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id){
        try{
            $item = NfseConfig::findOrFail($id);
            if($request->hasFile('file')){
                $file = $request->file('file');
                $extensao = $file->getClientOriginalExtension();
                $file_name = Str::random(25) . "." . $extensao;
                $file->move(public_path('logos'), $file_name);
            }else{
                $file_name = $item->logo;
            }

            $request->merge([
                'logo' => $file_name
            ]);

            // dd($request->all());
            $item->fill($request->all())->save();

            $resp = $this->atualizaSofthouse($request, $item);

            if($resp->codigo == 200){
                session()->flash("mensagem_sucesso", "Atualizado com sucesso!");
            }else{
                session()->flash('mensagem_erro', $resp->mensagem);
            }           

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();
    }

    private function atualizaSofthouse($request, $item){
        try {
            $config = ConfigSystem::first();

            $params = [
                'token' => $item->token,
                'ambiente' => Softhouse::AMBIENTE_PRODUCAO,
                'options' => [
                    'debug' => false,
                    'timeout' => 60,
                    'port' => 443,
                    'http_version' => CURL_HTTP_VERSION_NONE
                ]
            ];
            $softhouse = new Emitente($params);
            $documento = preg_replace('/[^0-9]/', '', $request->documento);
            $telefone = preg_replace('/[^0-9]/', '', $request->telefone);
            $cep = preg_replace('/[^0-9]/', '', $request->cep);

            $cidade = Cidade::findOrFail($request->cidade_id);

            $payload = [
                "nome" => $request->nome,
                "razao" => $request->razao_social,
                "cnae" => $request->cnae,
                "crt" => $request->regime == 'simples' ? 1 : 3,
                "ie" => $request->ie,
                "im" => $request->im,
                "login_prefeitura" => $request->login_prefeitura,
                "senha_prefeitura" => $request->senha_prefeitura,
                "telefone" => $telefone,
                "email" => $request->email,
                "rua" => $request->rua,
                "numero" => $request->numero,
                "complemento" => $request->complemento,
                "bairro" => $request->bairro,
                "municipio" => $cidade->nome, 
                "cmun" => $cidade->codigo, 
                "uf" => $cidade->uf, 
                "cep" => $cep,
                "plano" => 'Emitente',
                "documentos" => [
                    "nfse" => true,
                ]
            ];

            if(strlen($documento) == 11){
                $payload['cpf'] = $documento;
            }else{
                $payload['cnpj'] = $documento;
            }

            if($item->logo != null){
                if(file_exists(public_path('logos/').$item->logo)){
                    $file = file_get_contents(public_path('logos/').$item->logo);
                    $payload['logo'] = base64_encode($file);
                }
            }
            // dd($payload);
            $resp = $softhouse->atualiza($payload);

            return $resp;
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
    }

    public function removeLogo(){
        $item = NfseConfig::where('empresa_id', $this->empresa_id)
        ->first();
        if($item->logo != null && file_exists(public_path('logos/').$item->logo)){
            unlink(public_path('logos/').$item->logo);
        }

        $item->logo = null;
        $item->save();

        $params = [
            'token' => $item->token,
            'ambiente' => Softhouse::AMBIENTE_PRODUCAO,
            'options' => [
                'debug' => false,
                'timeout' => 60,
                'port' => 443,
                'http_version' => CURL_HTTP_VERSION_NONE
            ]
        ];
        $softhouse = new Emitente($params);

        $payload['logo'] = null;
        $resp = $softhouse->atualiza($payload);
        // dd($resp);

        session()->flash("mensagem_sucesso", "Logo removida com sucesso!");            
        return redirect()->back();

    }

    public function uploadCertificado(Request $request){
        // if(!is_dir(public_path('certificado_temp'))){
        //     mkdir(public_path('certificado_temp'), 0777, true);
        // }

        if(!$request->hasFile('file')){
            session()->flash('mensagem_erro', 'Selecione o Certificado!');
            return redirect()->back();
        }

        $file = base64_encode(file_get_contents($request->file('file')->path()));
        // dd($file);
        $senha = $request->senha;
        try {
            $config = ConfigSystem::first();
            $item = NfseConfig::where('empresa_id', $this->empresa_id)
            ->first();
            $params = [
                'token' => $item->token,
                'ambiente' => Certificado::AMBIENTE_PRODUCAO,
                'options' => [
                    'debug' => false,
                    'timeout' => 60,
                    'port' => 443,
                    'http_version' => CURL_HTTP_VERSION_NONE
                ]
            ];
            $certificado = new Certificado($params);

            $payload = [
                'certificado' => $file,
                'senha' => $senha
            ];

            $resp = $certificado->atualiza($payload);
            if($resp->codigo == 200){
                session()->flash('mensagem_sucesso', 'Upload realizado com sucesso!');
            }else{
                session()->flash('mensagem_erro', $resp->mensagem);
            }
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();
    }

    public function newToken(){
        try {
            $config = ConfigSystem::first();
            $item = NfseConfig::where('empresa_id', $this->empresa_id)
            ->first();
            $params = [
                'token' => $item->token,
                'ambiente' => Emitente::AMBIENTE_PRODUCAO,
                'options' => [
                    'debug' => false,
                    'timeout' => 60,
                    'port' => 443,
                    'http_version' => CURL_HTTP_VERSION_NONE
                ]
            ];
            $emitente = new Emitente($params);

            $resp = $emitente->token();
            dd($resp);
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();
    }

}
